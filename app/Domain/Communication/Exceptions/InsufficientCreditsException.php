<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct(
        public readonly int $userId,
        public readonly int $amount,
    ) {
        parent::__construct("Insufficient credits for user {$userId}: required {$amount}.");
    }
}
