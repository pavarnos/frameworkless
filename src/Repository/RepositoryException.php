<?php
/**
 * @file
 * @author Lightly Salted Software Ltd
 * @date   24 Nov 2020
 */

declare(strict_types=1);

namespace Frameworkless\Repository;

use Frameworkless\AppException;
use Throwable;

class RepositoryException extends AppException
{
    private string $table;

    private int $recordID;

    public function __construct(
        string $message,
        string $table,
        int $recordID = 0,
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->table    = $table;
        $this->recordID = $recordID;
        $message        .= ' for ' . $table;
        if (!empty($recordID)) {
            $message .= ' record ' . $recordID;
        }
        parent::__construct($message, $code, $previous);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecordID(): int
    {
        return $this->recordID;
    }
}