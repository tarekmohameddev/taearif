<?php

declare(strict_types=1);

namespace App\Domain\Calling\Exceptions;

use RuntimeException;

final class NoAgentExtensionException extends RuntimeException
{
    public function __construct(string $message = 'No active SIP extension for this agent', int $code = 409)
    {
        parent::__construct($message, $code);
    }
}
