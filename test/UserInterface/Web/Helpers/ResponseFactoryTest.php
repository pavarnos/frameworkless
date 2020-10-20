<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Helpers;

use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\WebActionTrait;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    use WebActionTrait;

    public function testHtmlResponse(): void
    {
        $response = ResponseFactory::htmlResponse($content = 'foo bar', $code = 222, ['X-Test' => 1]);
        self::assertEquals($content, $this->getResponseBody($response));
        self::assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        self::assertEquals('1', $response->getHeaderLine('X-Test'));
        self::assertEquals($code, $response->getStatusCode());
    }

    public function testJsonResponse(): void
    {
        $response = ResponseFactory::jsonResponse(
            $content = ['a' => 123, 'b' => 'foo bar'],
            $code = 222,
            ['X-Test' => 1]
        );
        self::assertEquals(\Safe\json_encode($content), $this->getResponseBody($response));
        self::assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        self::assertEquals('1', $response->getHeaderLine('X-Test'));
        self::assertEquals($code, $response->getStatusCode());
    }

    public function testErrorResponseHtml(): void
    {
        $exception = new HttpException(
            $content = 'foo bar',
            $code = 222,
            ['X-Test' => 1],
            $previous = new \InvalidArgumentException($inner = 'whoops')
        );
        $response  = ResponseFactory::errorResponse(
            new ServerRequest(HttpUtilities::METHOD_GET, '/', ['Accept' => 'text/html']),
            $exception
        );
        self::assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        self::assertEquals('1', $response->getHeaderLine('X-Test'));
        self::assertEquals($code, $response->getStatusCode());
        $html = $this->getResponseBody($response);
        self::assertStringContainsString($inner, $html);
        self::assertStringContainsString($content, $html);
    }

    public function testErrorResponse(): void
    {
        $exception = new HttpException(
            $content = 'foo bar',
            $code = 222,
            ['X-Test' => 1],
            $previous = new \InvalidArgumentException($inner = 'whoops')
        );
        $response  = ResponseFactory::errorResponse(
            new ServerRequest(HttpUtilities::METHOD_GET, '/', ['Accept' => 'application/json']),
            $exception
        );
        self::assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        self::assertEquals('1', $response->getHeaderLine('X-Test'));
        self::assertEquals($code, $response->getStatusCode());
        $data = \Safe\json_decode($this->getResponseBody($response), true);
        self::assertEquals($data['error'], $content);
        self::assertEquals($data['code'], $code);
        self::assertEquals($data['previous']['error'], $inner);
    }

    public function testStaticFile(): void
    {
        $diskPath = Environment::DOCUMENT_ROOT . '/.well-known/security.txt';
        $response = ResponseFactory::staticFileResponse($diskPath);
        self::assertEquals(\Safe\file_get_contents($diskPath), $this->getResponseBody($response));
        self::assertEquals('text/plain', $response->getHeaderLine('Content-Type'));
    }
}
