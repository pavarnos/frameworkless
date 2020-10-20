<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   13 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Carbon\Carbon;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProfileMiddleware implements MiddlewareInterface
{
    public const HEADER = 'X-Profile-Milliseconds';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $now = Carbon::now();
        $response = $handler->handle($request);
        return $response->withHeader(self::HEADER, (string) $now->diffInMilliseconds());
    }
}