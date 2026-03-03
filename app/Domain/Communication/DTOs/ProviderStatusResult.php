<?php

namespace App\Domain\Communication\DTOs;

use Carbon\CarbonInterface;

final class ProviderStatusResult
{
    public function __construct(
        public readonly string $provider_message_id,
        public readonly string $status,
        public readonly CarbonInterface $occurred_at,
        public readonly array $raw_payload = [],
    ) {}
}
