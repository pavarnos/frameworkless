<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   13 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Middleware;

use Frameworkless\UserInterface\Web\HttpException;
use Frameworkless\UserInterface\Web\HttpUtilities;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * simplified version of https://github.com/middlewares/payload and the one from reactphp/server
 */
class ParseBodyMiddleware implements MiddlewareInterface
{
    public const METHODS = [
        HttpUtilities::METHOD_POST,
        HttpUtilities::METHOD_PUT,
        HttpUtilities::METHOD_PATCH,
        HttpUtilities::METHOD_DELETE,
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array(strtoupper($request->getMethod()), self::METHODS)) {
            return $handler->handle($request);
        }

        [$type,] = explode(';', strtolower($request->getHeaderLine('Content-Type')));

        if ($type === 'application/json') {
            return $handler->handle(
                $request->withParsedBody(\Safe\json_decode($request->getBody()->getContents(), true))
            );
        }

        if ($type === 'application/x-www-form-urlencoded') {
            return $handler->handle($this->parseFormUrlencoded($request));
        }

        return $handler->handle($request);
    }

    private function parseFormUrlencoded(ServerRequestInterface $request): ServerRequestInterface
    {
        $data = [];
        $body = (string)$request->getBody();
        parse_str($body, $data);
        if (strlen($body) && empty($data)) {
            throw new HttpException('Invalid url encoded string');
        }
        return $request->withParsedBody($data);
    }
}