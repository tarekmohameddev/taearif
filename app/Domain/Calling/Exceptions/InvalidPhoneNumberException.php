<?php

declare(strict_types=1);

namespace App\Domain\Calling\Exceptions;

use RuntimeException;

final class InvalidPhoneNumberException extends RuntimeException
{
    public function __construct(string $message = 'Invalid phone number', int $code = 422)
    {
        parent::__construct($message, $code);
    }
}
