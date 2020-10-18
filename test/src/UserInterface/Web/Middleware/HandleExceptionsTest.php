<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   15 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\WebActionTrait;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HandleExceptionsTest extends TestCase
{
    use DispatcherFakerTrait, WebActionTrait;

    public function testThrowableException(): void
    {
        $logger    = new Logger('Test', [$handler = new TestHandler()]);
        $subject   = new HandleExceptions($logger);
        $exception = new \InvalidArgumentException($content = 'something bad happened');
        $response  = $subject->process($this->getRequest(), $this->exceptionDispatcher($exception));
        self::assertStringContainsString($content, $this->getResponseBody($response));
        self::assertEquals(HttpUtilities::STATUS_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testHttpException(): void
    {
        $logger    = new Logger('Test', [$handler = new TestHandler()]);
        $subject   = new HandleExceptions($logger);
        $inner     = new \InvalidArgumentException($innerMessage = 'something bad happened');
        $exception = new HttpException($message = 'oof it failed', $code = HttpUtilities::STATUS_FORBIDDEN, [], $inner);
        $response  = $subject->process($this->getRequest(), $this->exceptionDispatcher($exception));
        $html      = $this->getResponseBody($response);
        self::assertStringContainsString($innerMessage, $html);
        self::assertStringContainsString($message, $html);
        self::assertEquals($code, $response->getStatusCode());
    }

    public function testNoException(): void
    {
        $logger   = new Logger('Test', [$handler = new TestHandler()]);
        $subject  = new HandleExceptions($logger);
        $response = $subject->process($this->getRequest($content = 'Hello, World'), $this->echoDispatcher());
        self::assertStringContainsString($content, $this->getResponseBody($response));
        self::assertEquals(HttpUtilities::STATUS_OK, $response->getStatusCode());
    }

    private function getRequest(string $content = 'Hello World'): ServerRequestInterface
    {
        return new ServerRequest(HttpUtilities::METHOD_GET, '/', ['Content-Type' => 'text/html'], $content);
    }
}
