<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   4 10 2019
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\UserInterface\Web\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * ->add() middleware inner first, then outer.
 * the first / innermost middleware should always return a response or you will get an exception
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
    public const ERROR_MESSAGE = 'No handler for middleware';

    /** @var MiddlewareInterface[] */
    private array $middleware = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (empty($this->middleware)) {
            throw new HttpException(self::ERROR_MESSAGE);
        }
        $middleware = array_pop($this->middleware);
//        assert(!is_null($middleware)); // for phpstan
        return $middleware->process($request, $this);
    }
}