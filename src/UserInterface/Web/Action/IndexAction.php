<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Action;

use Frameworkless\UserInterface\Web\HandlesGetRequest;
use Frameworkless\UserInterface\Web\Helpers\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexAction implements HandlesGetRequest
{
    public function getMethod(ServerRequestInterface $request): ResponseInterface
    {
        return ResponseFactory::htmlResponse('Hello World');
    }
}