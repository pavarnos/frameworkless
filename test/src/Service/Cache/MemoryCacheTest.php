<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\Service\Cache;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MemoryCacheTest extends TestCase
{
    public function testSet(): void
    {
        $subject = new MemoryCache();
        self::assertFalse($subject->has($key = 'one'));
        $subject->set($key, $value = 1);
        self::assertTrue($subject->has($key));
        self::assertEquals($value, $subject->get($key));
        $subject->delete($key);
        self::assertFalse($subject->has($key));
    }

    public function testTTL(): void
    {
        $subject = new MemoryCache();
        $subject->set($key1 = 'one', $value = '111', $ttl = 10);
        $subject->increment($key2 = 'two', $ttl = 10);
        $subject->increment($key2);
        self::assertTrue($subject->has($key1));
        self::assertTrue($subject->has($key2));
        Carbon::setTestNow(Carbon::now()->addSeconds($ttl));
        self::assertFalse($subject->has($key1));
        self::assertFalse($subject->has($key2));
    }

    public function testClear(): void
    {
        $subject = new MemoryCache();
        $subject->set($key1 = 'one', $value = '111', $ttl = 10);
        $subject->increment($key2 = 'two', $ttl = 10);
        self::assertTrue($subject->has($key1));
        self::assertTrue($subject->has($key2));
        $subject->clear();
        self::assertFalse($subject->has($key1));
        self::assertFalse($subject->has($key2));
        // default values returned
        self::assertEquals(998, $subject->get($key1, 998));
        self::assertEquals(999, $subject->get($key2, 999));
    }

    public function testIncrement(): void
    {
        $subject = new MemoryCache();
        self::assertEquals(0, $subject->get($key = 'one', 0));

        self::assertEquals(1, $subject->increment($key));
        self::assertEquals(1, $subject->get($key = 'one', 0));

        self::assertEquals(2, $subject->increment($key));
        self::assertEquals(2, $subject->get($key = 'one', 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
