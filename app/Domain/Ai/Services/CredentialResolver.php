<?php

declare(strict_types=1);

namespace App\Domain\Ai\Services;

use App\Models\AiProviderCredential;

final class CredentialResolver
{
    // Allowlist of models tenants may bring their own key for.
    // Keys outside this list will be rejected.
    private const APPROVED_MODELS = [
        'gpt-5-nano', 'gpt-5-mini', 'gpt-5', 'gpt-4o', 'gpt-4o-mini',
        'claude-haiku-4-5', 'claude-haiku-3-5', 'claude-sonnet-4',
        'gemini-2.5-flash', 'gemini-2.0-flash',
        'deepseek-chat', 'deepseek-reasoner',
    ];

    public function resolveForTenant(int $tenantId): ?AiProviderCredential
    {
        return AiProviderCredential::query()
            ->where('user_id', $tenantId)
            ->where('active', true)
            ->first();
    }

    public function platformDefault(): ?AiProviderCredential
    {
        return AiProviderCredential::query()
            ->whereNull('user_id')
            ->where('is_platform_default', true)
            ->where('active', true)
            ->first();
    }

    public function isModelApproved(string $model): bool
    {
        return in_array($model, self::APPROVED_MODELS, true);
    }

    public static function approvedModels(): array
    {
        return self::APPROVED_MODELS;
    }
}
