<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Fig\Http\Message\RequestMethodInterface;
use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Monolog\Test\TestCase;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Safe\Exceptions\JsonException;

class ParseMiddlewareTest extends TestCase
{
    private ResponseInterface $response;

    public function testUnchanged(): void
    {
        $request  = new ServerRequest(HttpUtilities::METHOD_POST, 'http://foo.bar');
        $handler  = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                TestCase::assertEquals([], $request->getQueryParams());
                TestCase::assertEquals(null, $request->getParsedBody());
                return new Response();
            }
        };
        $response = (new ParseMiddleware())->process($request, $handler);
        self::assertEquals($this->response, $response);
    }

    public function testQueryParams(): void
    {
        $request  = new ServerRequest(HttpUtilities::METHOD_GET, 'http://foo.bar?a=1&b=23&c=info@example.com');
        $handler  = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                TestCase::assertEquals('1', HttpUtilities::getQueryString($request, 'a'));
                TestCase::assertEquals(23, HttpUtilities::getQueryInteger($request, 'b'));
                TestCase::assertEquals('info@example.com', HttpUtilities::getQueryEmail($request, 'c'));
                return new Response();
            }
        };
        $response = (new ParseMiddleware())->process($request, $handler);
        self::assertEquals($this->response, $response);
    }

    /**
     * @param string $method
     * @dataProvider getMethods
     * @throws JsonException
     */
    public function testJsonBody(string $method): void
    {
        $data     = ['a' => 1, 'b' => 'two', 'c' => 'info@example.com'];
        $request  = new ServerRequest(
            $method,
            'http://foo.bar',
            ['Content-Type' => 'application/json'],
            \Safe\json_encode($data)
        );
        $handler  = new class($data) implements RequestHandlerInterface {
            private array $data;

            public function __construct(array $data)
            {
                $this->data = $data;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                TestCase::assertEquals($this->data['a'], HttpUtilities::getBodyInteger($request, 'a'));
                TestCase::assertEquals($this->data['b'], HttpUtilities::getBodyString($request, 'b'));
                TestCase::assertEquals($this->data['c'], HttpUtilities::getBodyEmail($request, 'c'));
                TestCase::assertEquals($this->data, $request->getParsedBody());
                return new Response();
            }
        };
        $response = (new ParseMiddleware())->process($request, $handler);
        self::assertEquals($this->response, $response);
    }

    public function getMethods(): array
    {
        return [
            RequestMethodInterface::METHOD_POST  => [RequestMethodInterface::METHOD_POST],
            RequestMethodInterface::METHOD_PUT   => [RequestMethodInterface::METHOD_PUT],
            RequestMethodInterface::METHOD_PATCH => [RequestMethodInterface::METHOD_PATCH],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
    }
}
