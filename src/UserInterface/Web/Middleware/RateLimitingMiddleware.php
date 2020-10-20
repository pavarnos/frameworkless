<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Carbon\Carbon;
use Fig\Http\Message\StatusCodeInterface;
use Frameworkless\UserInterface\Web\HttpException;
use LSS\YACache\APCCache;
use LSS\YACache\CacheInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * limit the number of times an ip address can hit the server within a specified period
 */
class RateLimitingMiddleware implements MiddlewareInterface
{
    public const PERIOD_SECONDS      = 60;
    public const REQUESTS_PER_PERIOD = 100;

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new APCCache('rate-limit');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key               = sha1($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $remainingAttempts = max(0, self::REQUESTS_PER_PERIOD - $this->countRequests($key));
        $headers           = [
            'X-RateLimit-Limit'     => self::REQUESTS_PER_PERIOD,
            'X-RateLimit-Remaining' => $remainingAttempts,
        ];

        if ($remainingAttempts <= 0) {
            $secondsRemaining             = $this->getCounterSecondsRemaining($key);
            $headers['Retry-After']       = $secondsRemaining;
            $headers['X-RateLimit-Reset'] = $secondsRemaining;
            throw new HttpException(
                'Rate limited: try again later',
                StatusCodeInterface::STATUS_TOO_MANY_REQUESTS,
                $headers
            );
        }

        $response = $handler->handle($request);
        foreach ($headers as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, (string)$headerValue);
        }
        return $response;
    }

    private function countRequests(string $key): int
    {
        $result = $this->cache->increment($key, self::PERIOD_SECONDS);
        if ($result === 1) {
            $this->cache->set(
                $key . ':timer',
                Carbon::now()->getTimestamp() + self::PERIOD_SECONDS,
                self::PERIOD_SECONDS
            );
        }
        return $result;
    }

    private function getCounterSecondsRemaining(string $key): int
    {
        $expiresAt = $this->cache->get($key . ':timer', 0);
        return $expiresAt === 0 ? 0 : max(0, Carbon::now()->diffInRealSeconds(new Carbon($expiresAt), false));
    }
}