<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   20 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Helpers;

use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class HttpUtilitiesTest extends TestCase
{
    /**
     * @param string $accept
     * @param string $contentType
     * @param string $expected
     * @param string $default
     * @dataProvider getContentType
     */
    public function testGetContentType(
        string $contentType,
        string $accept,
        string $expected,
        string $default = 'broken'
    ): void {
        $request = new ServerRequest(
            HttpUtilities::METHOD_GET,
            '/',
            array_filter(['Content-Type' => $contentType, 'Accept' => $accept])
        );
        self::assertEquals($expected, HttpUtilities::getContentType($request, $default));
    }

    public function getContentType(): array
    {
        $default = 'foo-bar';
        return [
            'accept multipart' => [
                '',
                'multipart/form-data; boundary=974767299852498929531610575',
                $default,
                $default,
            ],
            'accept text' => [
                '',
                'text/plain; charset=UTF-8',
                $default,
                $default,
            ],
            'accept html1' => ['', 'text/html; charset=UTF-8', HttpUtilities::CONTENT_TYPE_HTML, $default],
            'accept html2' => [
                '',
                'application/xhtml+xml; charset=UTF-8',
                HttpUtilities::CONTENT_TYPE_HTML,
                $default,
            ],
            'accept json1' => [
                '',
                'application/json; charset=UTF-8',
                HttpUtilities::CONTENT_TYPE_JSON,
                $default,
            ],
            'accept json2' => ['', 'application/x-json', HttpUtilities::CONTENT_TYPE_JSON, $default],
            'accept json3' => ['', 'text/json', HttpUtilities::CONTENT_TYPE_JSON, $default],
            'content type multipart' => ['multipart/form-data', '', $default, $default],
            'content type text' => ['text/plain', '', $default, $default],
            'content type html1' => ['text/html', '', HttpUtilities::CONTENT_TYPE_HTML, $default],
            'content type html2' => ['application/xhtml+xml', '', HttpUtilities::CONTENT_TYPE_HTML, $default],
            'content type json1' => ['application/json', '', HttpUtilities::CONTENT_TYPE_JSON, $default],
            'content type json2' => ['application/x-json', '', HttpUtilities::CONTENT_TYPE_JSON, $default],
            'content type json3' => ['text/json', '', HttpUtilities::CONTENT_TYPE_JSON, $default],
        ];
    }

    public function testSanitiseEmail(): void
    {
        self::assertEquals('', HttpUtilities::sanitiseEmail('nope, not an email <script>barf'));
        self::assertEquals('foo+bar@example.com', HttpUtilities::sanitiseEmail(' foo+bar@example.com   '));
    }

    public function testSanitiseInteger(): void
    {
        $default = 987;
        self::assertEquals($default, HttpUtilities::sanitiseInteger(null, $default));
        self::assertEquals($default, HttpUtilities::sanitiseInteger('', $default));
        self::assertEquals($default, HttpUtilities::sanitiseInteger('  ', $default));
        self::assertEquals($default, HttpUtilities::sanitiseInteger(' 123', $default));
        self::assertEquals(123, HttpUtilities::sanitiseInteger('123  456', $default));
        self::assertEquals(123, HttpUtilities::sanitiseInteger('123.456', $default));
        self::assertEquals(123, HttpUtilities::sanitiseInteger('123e+10', $default));
        self::assertEquals(0, HttpUtilities::sanitiseInteger('0', $default));
        self::assertEquals(0, HttpUtilities::sanitiseInteger(0, $default));
        self::assertEquals(123, HttpUtilities::sanitiseInteger(123, $default));
    }
}
