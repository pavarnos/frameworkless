<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   10 Oct 2020
 */

declare(strict_types=1);

require '../vendor/autoload.php';

use Frameworkless\ContainerBuilder;
use Frameworkless\UserInterface\Web\Middleware\MiddlewareDispatcher;
use Narrowspark\HttpEmitter\SapiEmitter;
use Nyholm\Psr7Server\ServerRequestCreator;

$container = (new ContainerBuilder())->build();
$request   = $container->get(ServerRequestCreator::class)->fromGlobals();
$response  = $container->get(MiddlewareDispatcher::class)->handle($request);
(new SapiEmitter())->emit($response);