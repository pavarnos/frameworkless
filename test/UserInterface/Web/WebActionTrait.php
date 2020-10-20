<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   19 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

trait WebActionTrait
{
    public function getResponseBody(ResponseInterface $response): string
    {
        $stream = $response->getBody();
        $stream->rewind();
        return $stream->getContents();
    }

    public function getHtmlResponse(ResponseInterface $response): string
    {
        TestCase::assertEquals(200, $response->getStatusCode());
        TestCase::assertEquals('text/html', $response->getHeaderLine('Content-Type'));
        return $this->getResponseBody($response);
    }

    public function getJsonResponse(ResponseInterface $response): array
    {
        TestCase::assertEquals(200, $response->getStatusCode());
        TestCase::assertEquals('application/json', $response->getHeaderLine('Content-Type'));
        return \Safe\json_decode($this->getResponseBody($response), true);
    }
}