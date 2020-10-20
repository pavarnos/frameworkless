<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   17 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Helpers;

use Frameworkless\Environment;
use Frameworkless\UserInterface\Web\HttpException;
use Narrowspark\MimeType\MimeTypeExtensionGuesser;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handy tools for building standard responses. Reduces boilerplate code elsewhere
 */
final class ResponseFactory
{
    private const MAX_AGE = 60 * 60 * 24 * 365;

    public static function htmlResponse(
        string $content,
        int $status = HttpUtilities::STATUS_OK,
        array $headers = []
    ): ResponseInterface {
        $headers = array_merge(['Content-Type' => 'text/html'], $headers);
        return new Response($status, $headers, $content);
    }

    public static function jsonResponse(
        array $content,
        int $status = HttpUtilities::STATUS_OK,
        array $headers = []
    ): ResponseInterface {
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);
        return new Response($status, $headers, \Safe\json_encode($content));
    }

    public static function htmlErrorResponse(HttpException $exception): ResponseInterface
    {
        $body = '<html lang="en"><body>';
        $body .= '<h1>Server Error</h1>';
        $body .= '<p>System administrators have been notified. Please try again later.</p>';
        if (Environment::isDebug()) {
            $body     .= '<p>' . $exception->getMessage() . '</p>';
            $body     .= '<pre>' . $exception->getTraceAsString() . '</pre>';
            $previous = $exception->getPrevious();
            if ($previous !== null) {
                $body .= '<h2>Previous</h2>';
                $body .= '<p>' . $previous->getMessage() . '</p>';
                $body .= '<pre>' . $previous->getTraceAsString() . '</pre>';
            }
        }
        $body .= '</body></html>';
        $code = $exception->getCode() ?: HttpUtilities::STATUS_INTERNAL_SERVER_ERROR;
        // Chrome requires the content length to be exact for non 200 responses, but xdebug may have injected some extra html
        // so we can't always use a 500 server error status here
        return self::htmlResponse($body, $code, $exception->getHeaders());
    }

    public static function jsonErrorResponse(HttpException $exception): ResponseInterface
    {
        $content = [
            'error' => $exception->getMessage(),
            'code'  => $exception->getCode() ?: HttpUtilities::STATUS_INTERNAL_SERVER_ERROR,
        ];
        if (Environment::isDebug()) {
            $content['trace'] = $exception->getTrace();
            $previous         = $exception->getPrevious();
            if ($previous !== null) {
                $content['previous'] = [
                    'error' => $previous->getMessage(),
                    'code'  => $previous->getCode(),
                    'trace' => $previous->getTrace(),
                ];
            }
        }
        return self::jsonResponse($content, $content['code'], $exception->getHeaders());
    }

    public static function errorResponse(ServerRequestInterface $request, HttpException $exception): ResponseInterface
    {
        $type = HttpUtilities::getContentType($request);
        return $type === 'json'
            ? self::jsonErrorResponse($exception)
            : self::htmlErrorResponse($exception);
    }

    public static function staticFileResponse(string $diskPath): ResponseInterface
    {
        $contentType                    = self::calculateMimeType($diskPath);
        $headers                        = [];
        $headers['Expires']             = gmdate('r', time() + self::MAX_AGE);
        $headers['Content-Description'] = 'File Transfer';
        $headers['Content-Type']        = $contentType;
        $headers['Content-Disposition'] = 'attachment; filename="' . basename($diskPath) . '"';
        $headers['Cache-Control']       = 'max-age=' . self::MAX_AGE;
        $headers['Content-Length']      = \Safe\filesize($diskPath);
        $headers['Last-Modified']       = gmdate('r', \Safe\filemtime($diskPath));
        return new Response(HttpUtilities::STATUS_OK, $headers, \Safe\fopen($diskPath, 'r'));
    }

    private static function calculateMimeType(string $path): string
    {
        return MimeTypeExtensionGuesser::guess(pathinfo($path, PATHINFO_EXTENSION)) ?? 'application/octet-stream';
    }
}