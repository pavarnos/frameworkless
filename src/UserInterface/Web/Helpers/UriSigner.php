<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web\Helpers;

use Carbon\Carbon;
use Frameworkless\Environment;
use Psr\Http\Message\UriInterface;

/**
 * Match the algorithm from Laravel so our signed URLs are cross compatible.
 * APP_KEY must match the key used in Laravel
 */
class UriSigner
{
    private string $key;

    public function __construct(string $key = null)
    {
        $this->key = $key ?? Environment::getAppSecret();
    }

    public function isValid(UriInterface $uri): bool
    {
        parse_str($uri->getQuery(), $parameters);
        $signature = $parameters['signature'] ?? '';
        $expires   = intval($parameters['expires'] ?? 0);
        unset($parameters['signature']);
        return $expires > 0
            && hash_equals($this->calculateSignature($uri, $parameters), $signature)
            && Carbon::now()->getTimestamp() < $expires;
    }

    public function sign(UriInterface $uri, int $expiresAt): UriInterface
    {
        parse_str($uri->getQuery(), $parameters);
        $parameters['expires']   = $expiresAt;
        $parameters['signature'] = $this->calculateSignature($uri, $parameters);
        return $uri->withQuery(http_build_query($parameters));
    }

    private function calculateSignature(UriInterface $uri, array $parameters): string
    {
        assert(!isset($parameters['signature']));
        $url = $uri->withQuery(http_build_query($parameters))->__toString();
        return hash_hmac('sha256', $url, $this->key);
    }
}