<?php

declare(strict_types=1);

namespace App\Domain\Calling\Exceptions;

use RuntimeException;

final class CallingModuleDisabledException extends RuntimeException
{
    public function __construct(string $message = 'Calling module is not enabled for this tenant', int $code = 403)
    {
        parent::__construct($message, $code);
    }
}
