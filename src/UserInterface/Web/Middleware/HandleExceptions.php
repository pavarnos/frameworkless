<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   4 10 2019
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\HttpUtilities;
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
            } catch (\PDOException $ex) {
                // have to ignore it: unrecoverable
            }
            $code = $exception->getCode() ?: HttpUtilities::STATUS_INTERNAL_SERVER_ERROR;
            return HttpUtilities::htmlResponse($this->formatError($exception), $code);
            // Chrome requires the content length to be exact for non 200 responses, but xdebug may have injected some extra html
            // so we can't use a 500 server error status here
        }
    }

    private function formatError(\Throwable $exception): string
    {
        $body = '<html lang="en"><body>';
        $body .= '<h1>Server Error</h1>';
        $body .= '<p>System administrators have been notified. Please try again later.</p>';
        if (Environment::isDebug()) {
            $body .= '<p>' . $exception->getMessage() . '</p>';
            $body .= '<pre>' . $exception->getTraceAsString() . '</pre>';
        }
        $body .= '</body></html>';
        return $body;
    }
}
