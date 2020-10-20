<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Carbon\Carbon;
use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ProfileMiddlewareTest extends TestCase
{
    use DispatcherFakerTrait;

    private const DELTA = 50;

    public function testProcessNoDelay(): void
    {
        $subject  = new ProfileMiddleware();
        $response = $subject->process(new ServerRequest(HttpUtilities::METHOD_GET, '/'), $this->echoDispatcher());
        self::assertEqualsWithDelta(0, $response->getHeaderLine(ProfileMiddleware::HEADER), self::DELTA);
    }

    public function testProcessWithDelay(): void
    {
        $subject  = new ProfileMiddleware();
        $handler  = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                Carbon::setTestNow(Carbon::now()->addMilliseconds(111));
                return new Response();
            }
        };
        $response = $subject->process(new ServerRequest(HttpUtilities::METHOD_GET, '/'), $handler);
        self::assertEqualsWithDelta(111, $response->getHeaderLine(ProfileMiddleware::HEADER), self::DELTA);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
