<?php

namespace App\Domain\CustomersHub\Exceptions;

class StageInUseException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $requestsCount = 0
    ) {
        parent::__construct($message);
    }
}
