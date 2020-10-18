<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\Service\Cache;

/**
 * Very simple cache
 * We don't implement PSR-6 or PSR-16 because we
 * - need an increment function,
 * - want strict types,
 * - and don't need the getMultiple() and other *Multiple() functions
 * The interface otherwise follows PSR-16 as closely as possible, pending a new stricter release by PHP-FIG
 */
interface CacheInterface
{
    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     */
    public function set(string $key, $value, int $ttl = 0): void;

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    public function has(string $key): bool;

    /**
     * @param string $key
     * @param int    $ttl
     * @return int
     */
    public function increment(string $key, int $ttl = 0): int;

    public function delete(string $key): void;

    public function clear(): void;
}