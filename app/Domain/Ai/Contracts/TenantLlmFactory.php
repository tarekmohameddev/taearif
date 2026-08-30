<?php

declare(strict_types=1);

namespace App\Domain\Ai\Contracts;

interface TenantLlmFactory
{
    public function makeForTenant(int $tenantId, string $tier = 'chat'): LlmClient;
}

