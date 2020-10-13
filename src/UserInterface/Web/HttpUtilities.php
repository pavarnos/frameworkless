<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   10 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Frameworkless\Environment;
use Narrowspark\MimeType\MimeTypeExtensionGuesser;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpUtilities implements StatusCodeInterface, RequestMethodInterface
{
    private const MAX_AGE           = 60 * 60 * 24 * 365;
    private const DEFAULT_MIME_TYPE = 'application/octet-stream';

    private const CONTENT_TYPE = [
        'html' => ['text/html', 'application/xhtml+xml'],
        'json' => ['application/json', 'text/json', 'application/x-json'],
    ];

    public static function htmlResponse(string $content, int $status = self::STATUS_OK): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'text/html'], $content);
    }

    public static function jsonResponse(array $content, int $status = self::STATUS_OK): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'application/json'], \Safe\json_encode($content));
    }

    public static function htmlErrorResponse(\Throwable $exception): ResponseInterface
    {
        $body = '<html lang="en"><body>';
        $body .= '<h1>Server Error</h1>';
        $body .= '<p>System administrators have been notified. Please try again later.</p>';
        if (Environment::isDebug()) {
            $body .= '<p>' . $exception->getMessage() . '</p>';
            $body .= '<pre>' . $exception->getTraceAsString() . '</pre>';
        }
        $body .= '</body></html>';
        $code = $exception->getCode() ?: HttpUtilities::STATUS_INTERNAL_SERVER_ERROR;
        // Chrome requires the content length to be exact for non 200 responses, but xdebug may have injected some extra html
        // so we can't always use a 500 server error status here
        return self::htmlResponse($body, $code);
    }

    public static function jsonErrorResponse(\Throwable $exception): ResponseInterface
    {
        $content = [
            'error' => $exception->getMessage(),
            'code'  => $exception->getCode() ?: HttpUtilities::STATUS_INTERNAL_SERVER_ERROR,
        ];
        if (Environment::isDebug()) {
            $content['trace'] = $exception->getTrace();
        }
        return self::jsonResponse($content, $content['code']);
    }

    public static function errorResponse(ServerRequestInterface $request, \Throwable $exception): ResponseInterface
    {
        $type = HttpUtilities::getContentType($request);
        return $type === 'json'
            ? HttpUtilities::jsonErrorResponse($exception)
            : HttpUtilities::htmlErrorResponse($exception);
    }

    public static function getContentType(ServerRequestInterface $request, string $default = 'json'): string
    {
        // find the first known content type that the caller will Accept
        $accepts = explode(',', strtolower($request->getHeaderLine('Accept')));
        foreach ($accepts as $accept) {
            [$accept,] = explode(';', $accept);
            foreach (self::CONTENT_TYPE as $name => $mimeTypes) {
                if (in_array($accept, $mimeTypes)) {
                    return $name;
                }
            }
        }
        return $default;
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
        return new Response(self::STATUS_OK, $headers, \Safe\fopen($diskPath, 'r'));
    }

    public static function sanitiseRequestText(ServerRequestInterface $request, string $name): string
    {
        return self::sanitiseText($request->getQueryParams()[$name] ?? '');
    }

    public static function sanitiseText(string $message): string
    {
        return trim(strip_tags($message));
    }

    /**
     * @param int|string|null $taintedValue
     * @param int             $defaultValue
     * @return int
     */
    public static function sanitiseInteger($taintedValue, int $defaultValue = 0): int
    {
        if (empty($taintedValue)) {
            return $defaultValue;
        }
        if (\Safe\preg_match('|^([\d]+)|', (string)$taintedValue, $matches) > 0) {
            $taintedValue = $matches[1] ?? '';
        } else {
            $taintedValue = '';
        }
        return max(0, (int)filter_var($taintedValue, FILTER_SANITIZE_NUMBER_INT));
    }

    private static function calculateMimeType(string $path): string
    {
        return MimeTypeExtensionGuesser::guess(pathinfo($path, PATHINFO_EXTENSION)) ?? self::DEFAULT_MIME_TYPE;
    }
}