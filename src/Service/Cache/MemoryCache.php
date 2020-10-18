<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\Service\Cache;

use Carbon\Carbon;

class MemoryCache implements CacheInterface
{
    private string $prefix;

    /** @var mixed[] */
    private array $data = [];

    /** @var int[] */
    private array $expires = [];

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
        $this->clean();
        $this->data[$this->prefix . $key]    = $value;
        $this->expires[$this->prefix . $key] = $ttl > 0 ? Carbon::now()->addSeconds($ttl)->getTimestamp() : 0;
    }

    /**
     * @param string     $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $this->clean();
        return $this->data[$this->prefix . $key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->clean();
        return array_key_exists($this->prefix . $key, $this->data);
    }

    /**
     * @param string $key
     * @param int    $ttl
     * @return int
     */
    public function increment(string $key, int $ttl = 0): int
    {
        $this->clean();
        $id = $this->prefix . $key;
        if (isset($this->data[$id])) {
            return intval(++$this->data[$id]);
        }
        $this->set($key, 1, $ttl);
        return 1;
    }

    public function delete(string $key): void
    {
        unset($this->data[$this->prefix . $key]);
        unset($this->expires[$this->prefix . $key]);
    }

    public function clear(): void
    {
        $this->data    = [];
        $this->expires = [];
    }

    private function clean(): void
    {
        $now = Carbon::now()->getTimestamp();
        foreach ($this->expires as $key => $expires) {
            if ($expires > 0 && $expires <= $now) {
                unset($this->data[$key]);
                unset($this->expires[$key]);
            }
        }
    }
}