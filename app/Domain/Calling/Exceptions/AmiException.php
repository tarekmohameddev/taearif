<?php

declare(strict_types=1);

namespace App\Domain\Calling\Exceptions;

use RuntimeException;

final class AmiException extends RuntimeException
{
    public function __construct(string $message = 'AMI communication error', int $code = 503)
    {
        parent::__construct($message, $code);
    }
}
