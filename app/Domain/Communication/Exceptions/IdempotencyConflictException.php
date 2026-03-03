<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class IdempotencyConflictException extends RuntimeException
{
    public function __construct(
        public readonly string $reason,
        string $message = '',
    ) {
        parent::__construct($message ?: "Idempotency conflict: {$reason}");
    }
}
