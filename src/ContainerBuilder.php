<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   10 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless;

use Frameworkless\UserInterface\Web\Middleware\HandleExceptions;
use Frameworkless\UserInterface\Web\Middleware\JwtAuthMiddleware;
use Frameworkless\UserInterface\Web\Middleware\MiddlewareDispatcher;
use Frameworkless\UserInterface\Web\Middleware\ParseMiddleware;
use Frameworkless\UserInterface\Web\Middleware\ProfileMiddleware;
use Frameworkless\UserInterface\Web\Middleware\RouteMiddleware;
use LSS\YAContainer\Container;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Set up the dependency injection container to return objects wired together and configured for the app
 */
class ContainerBuilder
{
    /** @var array interface name => actual class to return. These all have auto wired constructors */
    private const ALIAS = [
        ServerRequestFactoryInterface::class => Psr17Factory::class,
        UriFactoryInterface::class           => Psr17Factory::class,
        UploadedFileFactoryInterface::class  => Psr17Factory::class,
        StreamFactoryInterface::class        => Psr17Factory::class,
    ];

    public function build(): ContainerInterface
    {
        $container = new Container([], self::ALIAS);

        $container->addFactory(
            LoggerInterface::class,
            function (): LoggerInterface {
                // log everything to the log file
                $stream = new StreamHandler(
                    Environment::BASE_PATH . 'var/log/error.log',
                    Logger::toMonologLevel(Environment::getString('LOG_LEVEL', 'Debug'))
                );
                // log error and above to syslog
                $syslog = new SyslogHandler(Environment::getAppName(), LOG_USER, Logger::ERROR);
                return new Logger(Environment::getAppName(), [$stream, $syslog]);
            }
        );

        $container->addFactory(
            MiddlewareDispatcher::class,
            fn(LoggerInterface $logger) => (new MiddlewareDispatcher())
                ->add(new RouteMiddleware($container))
                ->add(new JwtAuthMiddleware())
                ->add(new ParseMiddleware())
                ->add(new HandleExceptions($logger))
                ->add(new ProfileMiddleware())
        );
        return $container;
    }
}