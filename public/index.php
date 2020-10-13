<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   10 Oct 2020
 */

declare(strict_types=1);

use Frameworkless\ContainerBuilder;
use Frameworkless\Http\MiddlewareDispatcher;
use Nyholm\Psr7Server\ServerRequestCreator;
use Narrowspark\HttpEmitter\SapiEmitter;

$container = (new ContainerBuilder())->build();
$request   = $container->get(ServerRequestCreator::class)->fromGlobals();
$response  = $container->get(MiddlewareDispatcher::class)->handle($request);
(new SapiEmitter())->emit($response);