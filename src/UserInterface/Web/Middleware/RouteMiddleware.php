<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   23 Jun 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\HandlesGetRequest;
use Frameworkless\UserInterface\Web\HandlesPostRequest;
use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\HttpUtilities;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteMiddleware implements MiddlewareInterface
{
    public const BASE_NAMESPACE = 'Frameworkless\UserInterface\Web\Action\\';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function uriToClassName(string $uri): string
    {
        // converts /foo/bar-baz to Foo\BarBaz in the BASE_NAMESPACE
        $uri        = trim($uri, '/') ?: 'index';
        $namespaced = join(Environment::NAMESPACE_SEPARATOR, array_map('ucfirst', explode('/', strtolower($uri))));
        $titleCased = join('', array_map('ucfirst', explode('-', $namespaced)));
        return self::BASE_NAMESPACE . $titleCased . 'Action';
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $className = self::uriToClassName($request->getUri()->getPath());
        if (!$this->container->has($className)) {
            throw new HttpException('Page does not exist', HttpUtilities::STATUS_NOT_FOUND);
        }
        $method = strtoupper($request->getMethod());
        $action = $this->container->get($className); // abstract classes and interfaces will throw exception
        if ($method === HttpUtilities::METHOD_GET && $action instanceof HandlesGetRequest) {
            return $action->getMethod($request);
        }
        if ($method === HttpUtilities::METHOD_POST && $action instanceof HandlesPostRequest) {
            return $action->postMethod($request);
        }
        throw new HttpException($method . ' not allowed', HttpUtilities::STATUS_NOT_FOUND);
    }
}