<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Frameworkless\UserInterface\Web\Action\Api\V1\LoginAction;
use Frameworkless\UserInterface\Web\Action\IndexAction;
use Frameworkless\UserInterface\Web\HandlesGetRequest;
use Frameworkless\UserInterface\Web\HandlesPostRequest;
use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\WebActionTrait;
use LSS\YAContainer\Container;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteMiddlewareTest extends TestCase
{
    use DispatcherFakerTrait, WebActionTrait;

    public const HELLO_WORLD = 'Hello, World';

    public function testNameMangling(): void
    {
        self::assertEquals(IndexAction::class, RouteMiddleware::uriToClassName(''));
        self::assertEquals(IndexAction::class, RouteMiddleware::uriToClassName('/'));
        self::assertEquals(IndexAction::class, RouteMiddleware::uriToClassName('/index'));
        self::assertEquals(LoginAction::class, RouteMiddleware::uriToClassName('/api/v1/login'));
    }

    public function testNoSuchRoute(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionCode(HttpUtilities::STATUS_NOT_FOUND);
        (new RouteMiddleware(new Container()))->process(
            new ServerRequest(RequestMethodInterface::METHOD_GET, '/no-such/route'),
            $this->failingDispatcher()
        );
    }

    public function testNoSuchMethod(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('No such method on route');
        $container = new Container();
        $container->set(IndexAction::class, $this->fakeGetAction());
        (new RouteMiddleware($container))->process(
            new ServerRequest(RequestMethodInterface::METHOD_PURGE, '/'),
            $this->failingDispatcher()
        );
    }

    public function testGetOK(): void
    {
        $container = new Container();
        $container->set(IndexAction::class, $this->fakeGetAction());
        $response = (new RouteMiddleware($container))->process(
            new ServerRequest(RequestMethodInterface::METHOD_GET, '/', [], 'foo bar'),
            $this->echoDispatcher()
        );
        self::assertEquals(self::HELLO_WORLD, $this->getResponseBody($response));
    }

    public function testPostOK(): void
    {
        $container = new Container();
        $container->set(IndexAction::class, $this->fakePostAction());
        $response = (new RouteMiddleware($container))->process(
            new ServerRequest(RequestMethodInterface::METHOD_POST, '/', [], 'foo bar'),
            $this->echoDispatcher()
        );
        self::assertEquals(self::HELLO_WORLD, $this->getResponseBody($response));
    }

    private function fakeGetAction(): HandlesGetRequest
    {
        return new class implements HandlesGetRequest {
            public function getMethod(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(HttpUtilities::STATUS_OK, [], RouteMiddlewareTest::HELLO_WORLD);
            }
        };
    }

    private function fakePostAction(): HandlesPostRequest
    {
        return new class implements HandlesPostRequest {
            public function postMethod(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(HttpUtilities::STATUS_OK, [], RouteMiddlewareTest::HELLO_WORLD);
            }
        };
    }
}
