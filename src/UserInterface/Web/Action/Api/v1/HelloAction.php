<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Action\Api\v1;

use Frameworkless\UserInterface\Web\HandlesGetRequest;
use Frameworkless\UserInterface\Web\HttpUtilities;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HelloAction implements HandlesGetRequest
{
    public function getMethod(ServerRequestInterface $request): ResponseInterface
    {
        return HttpUtilities::jsonResponse(
            [
                'hello'   => 'world',
                'user_id' => HttpUtilities::getJwtUserId($request),
            ]
        );
    }
}