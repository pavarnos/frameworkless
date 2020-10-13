<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   03 Jul 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface HandlesGetRequest
{
    public function getMethod(ServerRequestInterface $request): ResponseInterface;
}