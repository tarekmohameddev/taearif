<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Exceptions;

use RuntimeException;

final class PipedriveApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatusCode = 0,
        private readonly ?array $responseBody = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getResponseBody(): ?array
    {
        return $this->responseBody;
    }

    public function isAuthError(): bool
    {
        return $this->httpStatusCode === 401;
    }

    public function isClientError(): bool
    {
        return $this->httpStatusCode >= 400 && $this->httpStatusCode < 500;
    }
}
