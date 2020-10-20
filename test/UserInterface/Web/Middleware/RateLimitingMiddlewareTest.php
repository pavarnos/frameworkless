<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Carbon\Carbon;
use Fig\Http\Message\RequestMethodInterface;
use Frameworkless\UserInterface\Web\HttpException;
use LSS\YACache\MemoryCache;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class RateLimitingMiddlewareTest extends TestCase
{
    use DispatcherFakerTrait;

    // save and restore $_SERVER
    private array $server = [];

    public function testOneRequest(): void
    {
        $subject = new RateLimitingMiddleware(new MemoryCache());
        $this->checkHeadersResponse($this->getResponse($subject), RateLimitingMiddleware::REQUESTS_PER_PERIOD - 1);
    }

    public function testTwoRequests(): void
    {
        $subject = new RateLimitingMiddleware(new MemoryCache());
        $this->getResponse($subject);
        $this->checkHeadersResponse($this->getResponse($subject), RateLimitingMiddleware::REQUESTS_PER_PERIOD - 2);
    }

    public function testTwoRequestsAfterAMinute(): void
    {
        $subject = new RateLimitingMiddleware(new MemoryCache());
        $this->getResponse($subject);
        $this->getResponse($subject);
        $this->getResponse($subject);
        Carbon::setTestNow(Carbon::now()->addSeconds(RateLimitingMiddleware::PERIOD_SECONDS + 1));
        $this->checkHeadersResponse($this->getResponse($subject), RateLimitingMiddleware::REQUESTS_PER_PERIOD - 1);
    }

    public function testRateLimited(): void
    {
        $remainingTime = RateLimitingMiddleware::PERIOD_SECONDS;
        $now           = Carbon::now();
        Carbon::setTestNow($now);
        $subject = new RateLimitingMiddleware(new MemoryCache());
        for ($index = 1; $index < RateLimitingMiddleware::REQUESTS_PER_PERIOD; $index++) {
            $this->getResponse($subject);
            if ($index % 10 == 0) {
                // spread these across some time
                Carbon::setTestNow($now->addSeconds(1));
                $remainingTime--;
            }
        }
        // one over the limit
        try {
            $this->getResponse($subject);
            self::fail('should throw exception');
        } catch (HttpException $ex) {
            $this->checkHeaders($ex->getHeaders(), 0, $remainingTime);
        }
    }

    public function testDifferentIP(): void
    {
        $subject = new RateLimitingMiddleware(new MemoryCache());
        $this->getResponse($subject);
        $this->getResponse($subject);
        $this->getResponse($subject);
        $this->getResponse($subject);
        $response1 = $this->getResponse($subject);

        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->getResponse($subject);
        $response2 = $this->getResponse($subject);

        $this->checkHeadersResponse($response1, RateLimitingMiddleware::REQUESTS_PER_PERIOD - 5);
        $this->checkHeadersResponse($response2, RateLimitingMiddleware::REQUESTS_PER_PERIOD - 2);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        $_SERVER = $this->server;
        parent::tearDown();
    }

    /**
     * @param RateLimitingMiddleware $subject
     * @return ResponseInterface
     */
    private function getResponse(RateLimitingMiddleware $subject): ResponseInterface
    {
        return $subject->process(
            new ServerRequest(RequestMethodInterface::METHOD_GET, '/foo'),
            $this->echoDispatcher()
        );
    }

    private function checkHeadersResponse(ResponseInterface $response, int $remaining, int $retryAfter = null): void
    {
        self::assertEquals(RateLimitingMiddleware::REQUESTS_PER_PERIOD, $response->getHeaderLine('X-RateLimit-Limit'));
        self::assertEquals($remaining, $response->getHeaderLine('X-RateLimit-Remaining'));
        if (!is_null($retryAfter)) {
            self::assertEquals($retryAfter, $response->getHeaderLine('X-RateLimit-Reset'));
            self::assertEquals($retryAfter, $response->getHeaderLine('Retry-After'));
        }
    }

    private function checkHeaders(array $headers, int $remaining, int $retryAfter = null): void
    {
        self::assertEquals(RateLimitingMiddleware::REQUESTS_PER_PERIOD, $headers['X-RateLimit-Limit']);
        self::assertEquals($remaining, $headers['X-RateLimit-Remaining']);
        if (!is_null($retryAfter)) {
            self::assertEquals($retryAfter, $headers['X-RateLimit-Reset']);
            self::assertEquals($retryAfter, $headers['Retry-After']);
        }
    }
}
