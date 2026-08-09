<?php

namespace App\Imports\Exceptions;

class RowValidationException extends \Exception
{
    public function __construct(
        string $message,
        public readonly ?string $field = null,
        public readonly ?int $row = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
