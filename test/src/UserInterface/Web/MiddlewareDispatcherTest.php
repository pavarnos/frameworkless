<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   14 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Frameworkless\UserInterface\Web\Middleware\MiddlewareDispatcher;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class MiddlewareDispatcherTest extends TestCase
{
    use MiddlewareFakerTrait;

    public function testEmpty(): void
    {
        $this->expectException(HttpException::class);
        $subject = new MiddlewareDispatcher();
        $subject->handle(new ServerRequest(HttpUtilities::METHOD_GET, '/foo'));
    }

    public function testNesting(): void
    {
        $subject = new MiddlewareDispatcher();
        $subject->add($this->getFinalMiddleware());
        $subject->add($this->getMiddleware('1'));
        $subject->add($this->getMiddleware('2'));
        $subject->add($this->getMiddleware('3'));
        $response = $subject->handle(new ServerRequest(HttpUtilities::METHOD_GET, '/foo'));
        $stream   = $response->getBody();
        $stream->rewind();
        self::assertEquals('inner123', $stream->getContents());
        self::assertEquals(['1', '2', '3'], $response->getHeader('X-Fake'));
        self::assertEquals('Last', $response->getHeaderLine('X-Last'));
    }
}
