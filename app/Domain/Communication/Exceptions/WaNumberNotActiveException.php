<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class WaNumberNotActiveException extends RuntimeException
{
    public function __construct(int $waNumberId, ?\Throwable $previous = null)
    {
        parent::__construct("WhatsApp number is not active: {$waNumberId}", 0, $previous);
    }
}
