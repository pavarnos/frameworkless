<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Action\Api\v1;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\HandlesPostRequest;
use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\HttpUtilities;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoginAction implements HandlesPostRequest
{
    public function postMethod(ServerRequestInterface $request): ResponseInterface
    {
        // find the user
        $body     = $request->getParsedBody();
        $userName = HttpUtilities::sanitiseText($body['username'] ?? '');
        $password = HttpUtilities::sanitiseText($body['password'] ?? '');
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
        return HttpUtilities::jsonResponse(
            [
                'token'   => JWT::encode($payload, Environment::getAppSecret()),
                'expires' => $expires,
//                'user'    => ['id' => $userId],
            ]
        );
    }
}