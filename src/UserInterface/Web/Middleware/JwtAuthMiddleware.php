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
use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\HttpUtilities;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JwtAuthMiddleware implements MiddlewareInterface
{
    private const BASE_URL    = '/api/vi/';
    private const IGNORE      = [self::BASE_URL . 'login'];

    private const HEADER      = 'Authorization';
    private const TOKEN_REGEX = '/^Bearer\s+(.*)$/i';
    private const COOKIE      = 'token';

    public const NOT_LOGGED_IN = 'Not logged in';
    public const USER_ID       = 'user_id';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();
        if (!str_starts_with($uri, self::BASE_URL)) {
            return $handler->handle($request);
        }
        foreach (self::IGNORE as $ignore) {
            if ($uri === $ignore) {
                return $handler->handle($request);
            }
        }
        $token = $this->fetchToken($request);
        try {
            $payload = (array)JWT::decode($token, Environment::getAppSecret());
            $userId  = intval($payload['sub'] ?? 0);
            if (empty($userId)) {
                // belt and braces: 'sub' should always be set if the token was issued / signed by us
                throw new HttpException(self::NOT_LOGGED_IN, HttpUtilities::STATUS_UNAUTHORIZED);
            }
            return $handler->handle($request->withAttribute(self::USER_ID, $userId));
        } catch (\Throwable $ex) {
            throw new HttpException(self::NOT_LOGGED_IN, HttpUtilities::STATUS_UNAUTHORIZED);
        }
    }

    private function fetchToken(ServerRequestInterface $request): string
    {
        // Check for token in header.
        $header = $request->getHeaderLine(self::HEADER);
        if (!empty($header) && \Safe\preg_match(self::TOKEN_REGEX, $header, $matches)) {
            return $matches[1];
        }

        // Token not found in header try a cookie.
        $cookieParams = $request->getCookieParams();
        if (!empty($cookieParams[self::COOKIE])) {
            return $cookieParams[self::COOKIE];
        };
        throw new HttpException(self::NOT_LOGGED_IN, HttpUtilities::STATUS_UNAUTHORIZED);
    }
}