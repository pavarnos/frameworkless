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

class APCCacheTest extends TestCase
{
    public function testSet(): void
    {
        $subject = new APCCache('test1');
        $subject2 = new APCCache('test1a');
        self::assertFalse($subject->has($key = 'one'));
        $subject->set($key, $value = 1);
        self::assertTrue($subject->has($key));
        self::assertFalse($subject2->has($key), 'not visible in other cache');
        self::assertEquals($value, $subject->get($key));
        $subject->delete($key);
        self::assertFalse($subject->has($key));
    }

    public function testClear(): void
    {
        $subject = new APCCache('test2');
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        (new APCCache())->clear();
        parent::tearDown();
    }
}
