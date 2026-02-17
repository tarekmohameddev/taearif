<?php

namespace App\Domain\Communication\DTOs;

final class ProviderDispatchResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $provider_message_id,
        public readonly bool $is_transient_failure,
        public readonly ?string $error_code,
        public readonly ?string $error_message,
        public readonly array $raw_response = [],
    ) {}

    public static function success(?string $providerMessageId = null, array $rawResponse = []): self
    {
        return new self(
            success: true,
            provider_message_id: $providerMessageId,
            is_transient_failure: false,
            error_code: null,
            error_message: null,
            raw_response: $rawResponse,
        );
    }

    public static function failure(
        bool $isTransient,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        array $rawResponse = []
    ): self {
        return new self(
            success: false,
            provider_message_id: null,
            is_transient_failure: $isTransient,
            error_code: $errorCode,
            error_message: $errorMessage,
            raw_response: $rawResponse,
        );
    }
}
