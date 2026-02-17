<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class WaNumberNotFoundException extends RuntimeException
{
    public function __construct(int $waNumberId, int $userId, ?\Throwable $previous = null)
    {
        parent::__construct("WhatsApp number not found or not owned by tenant: {$waNumberId}", 0, $previous);
    }
}
