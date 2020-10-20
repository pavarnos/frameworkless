<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   19 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait DispatcherFakerTrait
{
    private function failingDispatcher(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                TestCase::fail('should not be called');
            }
        };
    }

    private function exceptionDispatcher(\Throwable $ex): RequestHandlerInterface
    {
        return new class($ex) implements RequestHandlerInterface {
            private \Throwable $ex;

            public function __construct(\Throwable $ex)
            {
                $this->ex = $ex;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->ex;
            }
        };
    }

    private function echoDispatcher(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $stream = $request->getBody();
                $stream->rewind();
                return new Response(HttpUtilities::STATUS_OK, [], $stream->getContents());
            }
        };
    }
}