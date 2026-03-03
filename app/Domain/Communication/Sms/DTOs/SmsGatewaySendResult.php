<?php

namespace App\Domain\Communication\Sms\DTOs;

final class SmsGatewaySendResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $gatewayMessageId,
        public readonly string $provider,
        public readonly ?string $error = null,
        public readonly array $rawResponse = [],
        public readonly bool $isTransientFailure = false,
    ) {}
}

