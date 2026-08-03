<?php

declare(strict_types=1);

namespace App\Domain\Ai\Services;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\Drivers\AnthropicDriver;
use App\Domain\Ai\Drivers\OpenAiCompatibleDriver;
use App\Domain\Ai\Exceptions\LlmProviderException;
use App\Models\AiProviderCredential;
use Illuminate\Support\Facades\Cache;

final class LlmDriverFactory
{
    private const PLATFORM_CACHE_TTL = 60; // seconds

    public function makeForTenant(int $tenantId, string $tier = 'chat'): LlmClient
    {
        $credential = $this->resolveCredential($tenantId);
        return $this->buildDriver($credential, $tier);
    }

    public function makePlatform(string $tier = 'chat'): LlmClient
    {
        $credential = $this->resolvePlatformCredential();
        return $this->buildDriver($credential, $tier);
    }

    private function resolveCredential(int $tenantId): AiProviderCredential
    {
        // Tenant BYO key takes priority
        $tenant = AiProviderCredential::query()
            ->where('user_id', $tenantId)
            ->where('active', true)
            ->first();

        if ($tenant !== null) {
            return $tenant;
        }

        return $this->resolvePlatformCredential();
    }

    private function resolvePlatformCredential(): AiProviderCredential
    {
        $cacheKey = 'ai.platform.credential';
        $cached   = Cache::remember($cacheKey, self::PLATFORM_CACHE_TTL, function () {
            return AiProviderCredential::query()
                ->whereNull('user_id')
                ->where('is_platform_default', true)
                ->where('active', true)
                ->first();
        });

        if ($cached === null) {
            return $this->envFallbackCredential();
        }

        return $cached;
    }

    private function envFallbackCredential(): AiProviderCredential
    {
        $cred                    = new AiProviderCredential();
        $cred->provider          = 'openai_compat';
        $cred->base_url          = 'https://api.openai.com/v1';
        // Use config() — env() returns null when config is cached (production).
        $cred->chat_model        = (string) config('openai.chat_model', 'gpt-5-mini');
        $cred->fast_model        = (string) config('openai.fast_model', 'gpt-5-nano');
        $cred->embedding_model   = (string) config('openai.embedding_model', 'text-embedding-3-small');
        $cred->setAttribute('_env_key', (string) config('openai.api_key', ''));
        return $cred;
    }

    private function buildDriver(AiProviderCredential $credential, string $tier): LlmClient
    {
        $apiKey   = $credential->getAttribute('_env_key') ?? $credential->getDecryptedKey();
        $provider = $credential->provider;

        if ($provider === 'anthropic') {
            return new AnthropicDriver($apiKey);
        }

        // openai_compat (default)
        $baseUrl = $credential->base_url ?? 'https://api.openai.com/v1';
        $label   = $this->providerLabel($baseUrl);
        return new OpenAiCompatibleDriver($apiKey, $baseUrl, $label);
    }

    private function providerLabel(string $baseUrl): string
    {
        if (str_contains($baseUrl, 'openai.com')) {
            return 'openai';
        }
        if (str_contains($baseUrl, 'deepseek')) {
            return 'deepseek';
        }
        if (str_contains($baseUrl, 'googleapis') || str_contains($baseUrl, 'google')) {
            return 'google';
        }
        if (str_contains($baseUrl, 'groq')) {
            return 'groq';
        }
        if (str_contains($baseUrl, 'openrouter')) {
            return 'openrouter';
        }
        if (str_contains($baseUrl, 'anthropic')) {
            return 'anthropic';
        }
        return 'custom';
    }
}
