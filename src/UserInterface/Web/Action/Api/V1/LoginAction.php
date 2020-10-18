<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Action\Api\V1;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\HandlesPostRequest;
use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Frameworkless\UserInterface\Web\Helpers\ResponseFactory;
use Frameworkless\UserInterface\Web\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginAction implements HandlesPostRequest
{
    public function postMethod(ServerRequestInterface $request): ResponseInterface
    {
        // find the user
        $body     = (array)$request->getParsedBody();
        $userName = HttpUtilities::getBodyString($request, 'username');
        $password = HttpUtilities::getBodyString($request, 'password');
        if ($userName !== 'foo@example.com' || $password !== 'password') {
            throw new HttpException('Invalid username or password', HttpUtilities::STATUS_UNAUTHORIZED);
        }
        $userId = 123;

        // return the token
        $now     = Carbon::now()->getTimestamp();
        $expires = $now + Environment::getJWTTokenValidSeconds();
        $payload = [
            'iat' => $now,
            'nbf' => $now,
            'exp' => $expires,
            'iss' => $request->getUri()->getHost(),
            'sub' => (string)$userId, // user id
            'jti' => bin2hex(random_bytes(10)),
        ];
        return ResponseFactory::jsonResponse(
            [
                'token'   => JWT::encode($payload, Environment::getAppSecret()),
                'expires' => $expires,
                // 'user'    => ['id' => $userId],
            ]
        );
    }
}