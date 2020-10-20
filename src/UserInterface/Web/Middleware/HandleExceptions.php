<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   4 10 2019
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Frameworkless\UserInterface\Web\Helpers\ResponseFactory;
use Frameworkless\UserInterface\Web\HttpException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/**
 * Emergency error that was not caught by anything else.
 * These are usually coding errors because application errors are all handled deeper in.
 * All we can do is send a generic error page and a 500 status code
 */
class HandleExceptions implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        try {
            return $next->handle($request);
        } catch (\Throwable $exception) {
            try {
                $this->logger->error($exception->getMessage(), ['trace' => $exception->getTraceAsString()]);
                // @codeCoverageIgnoreStart
            } catch (\PDOException $ex) {
                // database error logging the exception: have to ignore it: unrecoverable
                // @codeCoverageIgnoreEnd
            }
            return ResponseFactory::errorResponse($request, $this->standardise($exception));
        }
    }

    private function standardise(\Throwable $exception): HttpException
    {
        if ($exception instanceof HttpException) {
            return $exception;
        }
        // wrap unexpected exception in something we can handle
        return new HttpException($exception->getMessage(), HttpUtilities::STATUS_INTERNAL_SERVER_ERROR, [], $exception);
    }
}
