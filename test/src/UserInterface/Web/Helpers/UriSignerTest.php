<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Helpers;

use Carbon\Carbon;
use Nyholm\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class UriSignerTest extends TestCase
{
    public function testValid(): void
    {
        $expiresAt = Carbon::now()->addSeconds(10)->getTimestamp();
        $subject   = new UriSigner('the-password');
        self::assertTrue($subject->isValid($subject->sign(new Uri(), $expiresAt)));
        self::assertTrue(
            $subject->isValid($subject->sign(new Uri('https://foo.bar:8000/baz/bat?a=bcdef'), $expiresAt))
        );
    }

    public function testInValidTime(): void
    {
        Carbon::setTestNow($now = Carbon::now());
        $expiresAt = $now->copy()->addSeconds($delta = 10)->getTimestamp();
        $subject   = new UriSigner('the-password');
        $uri       = $subject->sign(new Uri('https://foo.bar:8000/baz/bat?a=bcdef'), $expiresAt);
        self::assertTrue($subject->isValid($uri));

        Carbon::setTestNow($now->addSeconds($delta + 1));
        self::assertFalse($subject->isValid($uri));
    }

    public function testInValidContent(): void
    {
        $expiresAt = Carbon::now()->addSeconds(10)->getTimestamp();
        $subject   = new UriSigner('the-password');
        $uri       = $subject->sign(new Uri('https://foo.bar:8000/baz/bat?a=bcdef'), $expiresAt);
        self::assertTrue($subject->isValid($uri));
        $url = $uri->__toString();
        self::assertFalse($subject->isValid(new Uri(str_replace('bcd', 'defg', $url))), 'change a parameter value');
        self::assertFalse($subject->isValid(new Uri(str_replace('https', 'http', $url))), 'change scheme');
        self::assertFalse($subject->isValid(new Uri(str_replace('foo.bar', 'ding.dong', $url))), 'change domain');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }
}
