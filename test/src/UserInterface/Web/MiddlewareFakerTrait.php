<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   15 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait MiddlewareFakerTrait
{
    private function getMiddleware(string $char): MiddlewareInterface
    {
        return new class($char) implements MiddlewareInterface {
            private string $char;

            public function __construct(string $char = '.')
            {
                $this->char = $char;
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
            {
                $response = $next->handle($request);
                $response = $response->withAddedHeader('X-Fake', $this->char);
                $response->getBody()->write($this->char);
                return $response;
            }
        };
    }

    private function getFinalMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
            {
                return new Response(HttpUtilities::STATUS_OK, ['X-Last' => 'Last'], 'inner');
            }
        };
    }

}