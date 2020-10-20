<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   10 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Helpers;

use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * collection of helper utilities that reduce the boilerplate in the code and standardise responses
 *
 * probably could split this into ResponseFactory
 */
final class HttpUtilities implements StatusCodeInterface, RequestMethodInterface
{
    public const CONTENT_TYPE_JSON      = 'json';
    public const CONTENT_TYPE_HTML      = 'html';
    public const CONTENT_TYPE_URLENCODE = 'urlencoded';
    public const CONTENT_TYPE_DEFAULT   = self::CONTENT_TYPE_HTML;

    // public for unit tests only
    public const CONTENT_TYPE = [
        self::CONTENT_TYPE_HTML      => ['text/html', 'application/xhtml+xml'],
        self::CONTENT_TYPE_JSON      => ['application/json', 'text/json', 'application/x-json'],
        self::CONTENT_TYPE_URLENCODE => ['application/x-www-form-urlencoded'],
    ];

    public static function getContentType(
        ServerRequestInterface $request,
        string $default = self::CONTENT_TYPE_DEFAULT
    ): string {
        // find the first known content type that the caller will Accept
        $accepts = explode(',', strtolower($request->getHeaderLine('Accept')));
        foreach ($accepts as $accept) {
            [$accept,] = explode(';', $accept);
            foreach (self::CONTENT_TYPE as $name => $mimeTypes) {
                if (in_array($accept, $mimeTypes, true)) {
                    return $name;
                }
            }
        }
        $accept = strtolower($request->getHeaderLine('Content-Type'));
        foreach (self::CONTENT_TYPE as $name => $mimeTypes) {
            if (in_array($accept, $mimeTypes, true)) {
                return $name;
            }
        }
        return $default;
    }

    public static function getQueryEmail(ServerRequestInterface $request, string $name = 'email'): string
    {
        return self::sanitiseEmail($request->getQueryParams()[$name] ?? '');
    }

    public static function getQueryString(ServerRequestInterface $request, string $name, string $default = ''): string
    {
        return self::sanitiseText($request->getQueryParams()[$name] ?? $default) ?: $default;
    }

    public static function getQueryInteger(ServerRequestInterface $request, string $name, int $default = 0): int
    {
        return self::sanitiseInteger($request->getQueryParams()[$name] ?? $default, $default);
    }

    public static function getBodyEmail(ServerRequestInterface $request, string $name = 'email'): string
    {
        return self::sanitiseEmail(((array)$request->getParsedBody())[$name] ?? '');
    }

    public static function getBodyString(ServerRequestInterface $request, string $name, string $default = ''): string
    {
        return self::sanitiseText(((array)$request->getParsedBody())[$name] ?? $default) ?: $default;
    }

    public static function getBodyInteger(ServerRequestInterface $request, string $name, int $default = 0): int
    {
        return self::sanitiseInteger(((array)$request->getParsedBody())[$name] ?? $default, $default);
    }

    public static function sanitiseText(string $message): string
    {
        return trim(filter_var($message, FILTER_SANITIZE_STRING) ?: '');
    }

    public static function sanitiseEmail(string $email): string
    {
        $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL) ?: '');
        if (\Safe\preg_match('|@.+\..+|', $email) <= 0) {
            return '';
        }
        return $email;
    }

    /**
     * @param int|string|null $taintedValue
     * @param int             $defaultValue
     * @return int
     */
    public static function sanitiseInteger($taintedValue, int $defaultValue = 0): int
    {
        if (is_null($taintedValue)) {
            return $defaultValue;
        }
        if (is_int($taintedValue)) {
            return $taintedValue;
        }
        if (\Safe\preg_match('|^([\d]+)|', $taintedValue, $matches) > 0) {
            $taintedValue = $matches[1] ?? $defaultValue;
        } else {
            return $defaultValue;
        }
        return max(0, (int)filter_var($taintedValue, FILTER_SANITIZE_NUMBER_INT));
    }
}