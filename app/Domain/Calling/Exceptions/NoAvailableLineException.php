<?php

declare(strict_types=1);

namespace App\Domain\Calling\Exceptions;

use RuntimeException;

final class NoAvailableLineException extends RuntimeException
{
    public function __construct(string $message = 'No available SIM line for this tenant', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
