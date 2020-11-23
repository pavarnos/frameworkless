<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   12 Oct 2020
 */

declare(strict_types=1);

namespace Frameworkless\UserInterface\Web;

use Frameworkless\AppException;
use Throwable;

class HttpException extends AppException
{
    private array $headers = [];

    public function __construct(string $message = "", int $code = 0, array $headers = [], Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->headers = $headers;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }
}