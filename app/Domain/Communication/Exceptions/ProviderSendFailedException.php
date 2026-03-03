<?php

namespace App\Domain\Communication\Exceptions;

use RuntimeException;

class ProviderSendFailedException extends RuntimeException
{
    public function __construct(string $message = 'Provider send failed.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
