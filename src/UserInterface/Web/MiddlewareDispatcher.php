<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   4 10 2019
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * the final / innermost middleware should always return a response
 */
class MiddlewareDispatcher implements RequestHandlerInterface
{
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
            throw new HttpException('No handler for middleware');
        }
        $middleware = array_pop($this->middleware);
        assert(!is_null($middleware)); // for phpstan
        return $middleware->process($request, $this);
    }
}