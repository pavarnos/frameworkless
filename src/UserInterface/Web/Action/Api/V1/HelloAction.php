<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Action\Api\V1;

use Frameworkless\UserInterface\Web\HandlesGetRequest;
use Frameworkless\UserInterface\Web\Helpers\ResponseFactory;
use Frameworkless\UserInterface\Web\Middleware\JwtAuthMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HelloAction implements HandlesGetRequest
{
    public function getMethod(ServerRequestInterface $request): ResponseInterface
    {
        return ResponseFactory::jsonResponse(
            [
                'hello'   => 'world',
                'user_id' => JwtAuthMiddleware::getUserId($request),
            ]
        );
    }
}