<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   14 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless;

use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testGetString(): void
    {
        self::assertEquals('', Environment::getString($key = 'NoSuch'));
        self::assertEquals($value = 'foo', Environment::getString($key, $value));
        Environment::set($key, $value);
        self::assertEquals($value, Environment::getString($key));
    }

    public function testGetInt(): void
    {
        self::assertEquals(0, Environment::getInt($key = 'NoSuch'));
        self::assertEquals($value = 1001, Environment::getInt($key, $value));
        Environment::set($key, (string)$value);
        self::assertEquals($value, Environment::getInt($key));
    }

    public function testGetBoolean(): void
    {
        self::assertEquals(false, Environment::getBoolean($key = 'NoSuch'));
        self::assertEquals($value = true, Environment::getBoolean($key, $value));
        Environment::set($key, '1');
        self::assertEquals($value, Environment::getBoolean($key));
    }

    public function getPredefined(): void
    {
        self::assertNotEmpty(Environment::getAppSecret());
        self::assertNotEmpty(Environment::getAppName());
        self::assertTrue(Environment::isDebug());
    }

    protected function tearDown(): void
    {
        Environment::reset();
        parent::tearDown();
    }
}
