<?php

declare(strict_types=1);

namespace App\Domain\Ai\Exceptions;

final class LlmProviderException extends \RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $provider,
        public readonly string $errorCode = 'provider_error',
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
