<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   13 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\UserInterface\Web\Helpers\HttpUtilities;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * simplified version of https://github.com/middlewares/payload and the one from reactphp/server
 */
class ParseMiddleware implements MiddlewareInterface
{
    public const METHODS = [
        HttpUtilities::METHOD_POST,
        HttpUtilities::METHOD_PUT,
        HttpUtilities::METHOD_PATCH,
        HttpUtilities::METHOD_DELETE,
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        parse_str($request->getUri()->getQuery(), $query);
        if (!empty($query)) {
            $request = $request->withQueryParams($query);
        }
        if (in_array($request->getMethod(), self::METHODS, true) && $request->getParsedBody() === null) {
            $request = $this->parseBody($request);
        }
        return $handler->handle($request);
    }

    public function parseBody(ServerRequestInterface $request): ServerRequestInterface
    {
        $contentType = HttpUtilities::getContentType($request);
        $stream      = $request->getBody();
        $stream->rewind();
        if ($contentType === HttpUtilities::CONTENT_TYPE_JSON) {
            return $request->withParsedBody(\Safe\json_decode($stream->getContents() ?: '[]', true));
        }

        if ($contentType === HttpUtilities::CONTENT_TYPE_URLENCODE) {
            parse_str($stream->getContents(), $data);
            return $request->withParsedBody($data);
        }

        // other content types do not need parsing
        return $request;
    }
}