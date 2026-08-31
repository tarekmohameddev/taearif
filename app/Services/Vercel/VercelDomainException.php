<?php

namespace App\Services\Vercel;

use RuntimeException;

class VercelDomainException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly mixed $responseBody = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ?string
    {
        if (! is_array($this->responseBody)) {
            return null;
        }

        $code = $this->responseBody['error']['code'] ?? null;

        return is_string($code) ? $code : null;
    }
}
