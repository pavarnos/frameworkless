<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Jun 2020
 */

declare(strict_types=1);

namespace Frameworkless;

// evil hack because php cannot call dirname() when defining a class constant needed for Environment::BASE_PATH
define('REAL_BASE_PATH', dirname(__DIR__) . '/');

/**
 * .env file: parse the file for config values
 */
class Environment
{
    public const BASE_PATH           = REAL_BASE_PATH;
    public const CACHE_PATH          = self::BASE_PATH . 'cache/';
    public const SRC_PATH            = self::BASE_PATH . 'src/';
    public const DOCUMENT_ROOT       = self::BASE_PATH . 'public'; // must have no trailing slash
    public const ASSET_PATH          = '/assets/';
    public const NAMESPACE_SEPARATOR = '\\';
    public const BR                  = '<br>';

    /** @var string[] */
    private static array $data = [];

    public static function getString(string $name, string $default = ''): string
    {
        self::loadFile();
        return self::$data[$name] ?? $default;
    }

    public static function getInt(string $name, int $default = 0): int
    {
        self::loadFile();
        return intval(self::$data[$name] ?? $default);
    }

    public static function getBoolean(string $name, bool $default = false): bool
    {
        self::loadFile();
        return !empty(self::$data[$name] ?? $default);
    }

    public static function getAppName(): string
    {
        return self::getString('APP_NAME', 'My App');
    }

    public static function getAppSecret(): string
    {
        return self::getString('APP_SECRET');
    }

    public static function getJWTTokenValidSeconds(): int
    {
        return self::getInt('JWT_VALID_SECONDS', 1440);
    }

    public static function isDebug(): bool
    {
        return self::getString('LOG_LEVEL') === 'Debug' || defined('UNIT_TEST');
    }

    /**
     * @param string $key
     * @param string $value
     * @internal for unit tests only
     */
    public static function set(string $key, string $value): void
    {
        self::$data[$key] = $value;
    }

    /**
     * @internal for unit tests only
     */
    public static function reset(): void
    {
        self::$data = [];
    }

    private static function loadFile(): void
    {
        if (empty(self::$data)) {
            self::$data = \Safe\parse_ini_file(self::BASE_PATH . '.env', false, INI_SCANNER_TYPED);
        }
    }
}