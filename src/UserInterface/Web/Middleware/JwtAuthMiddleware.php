<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Firebase\JWT\JWT;
use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Frameworkless\UserInterface\Web\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{
    private const BASE_URL = '/api/';
    private const IGNORE   = [self::BASE_URL . 'v1/login'];

    private const HEADER = 'Authorization';
    private const COOKIE = 'token';

    public const NOT_LOGGED_IN = 'Not logged in';
    public const USER_ID       = 'user_id';

    public static function getUserId(ServerRequestInterface $request): int
    {
        return HttpUtilities::sanitiseInteger($request->getAttribute(self::USER_ID, 0));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->shouldHaveToken($request)) {
            return $handler->handle($request);
        }
        $token = $this->fetchToken($request);
        try {
            $payload = (array)JWT::decode($token, Environment::getAppSecret(), ['HS256']);
            $userId  = intval($payload['sub'] ?? 0);
            return $handler->handle($request->withAttribute(self::USER_ID, $userId));
        } catch (\Throwable $ex) {
            throw new HttpException(self::NOT_LOGGED_IN, HttpUtilities::STATUS_UNAUTHORIZED, [], $ex);
        }
    }

    private function shouldHaveToken(ServerRequestInterface $request): bool
    {
        $uri = $request->getUri()->getPath();
        if (!str_starts_with($uri, self::BASE_URL)) {
            return false;
        }
        foreach (self::IGNORE as $ignore) {
            if ($uri === $ignore) {
                return false;
            }
        }
        return true;
    }

    private function fetchToken(ServerRequestInterface $request): string
    {
        // Check for token in header.
        $token = trim(str_replace('Bearer', '', $request->getHeaderLine(self::HEADER)));
        if (!empty($token)) {
            return $token;
        }

        // Token not found in header try a cookie.
        $cookie = $request->getCookieParams()[self::COOKIE] ?? '';
        if (!empty($cookie)) {
            return $cookie;
        };
        throw new HttpException(self::NOT_LOGGED_IN, HttpUtilities::STATUS_UNAUTHORIZED);
    }
}