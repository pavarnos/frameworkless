<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\Service\Cache;

class APCCache implements CacheInterface
{
    private string $prefix;

    public function __construct(string $prefix = '')
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     */
    public function set(string $key, $value, int $ttl = 0): void
    {
        apcu_store($this->prefix . $key, $value, $ttl);
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }
        return \Safe\apcu_fetch($this->prefix . $key) ?: $default;
    }

    public function has(string $key): bool
    {
        return !empty(apcu_exists($this->prefix . $key));
    }

    /**
     * @param string $key
     * @param int    $ttl
     * @return int
     */
    public function increment(string $key, int $ttl = 0): int
    {
        return \Safe\apcu_inc($this->prefix . $key, 1, $ignored, $ttl);
    }

    public function delete(string $key): void
    {
        apcu_delete($this->prefix . $key);
    }

    public function clear(): void
    {
        if (empty($this->prefix)) {
            apcu_clear_cache();
        } else {
            apcu_delete(new \APCUIterator('|' . $this->prefix . '.*|'));
        }
    }
}