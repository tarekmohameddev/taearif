<?php

declare(strict_types=1);

namespace App\Domain\Ai\Services;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\Contracts\TenantLlmFactory;

final class DefaultTenantLlmFactory implements TenantLlmFactory
{
    public function __construct(
        private readonly LlmDriverFactory $inner,
    ) {}

    public function makeForTenant(int $tenantId, string $tier = 'chat'): LlmClient
    {
        return $this->inner->makeForTenant($tenantId, $tier);
    }
}

