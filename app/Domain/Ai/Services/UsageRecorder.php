<?php

declare(strict_types=1);

namespace App\Domain\Ai\Services;

use App\Domain\Ai\DTOs\LlmResponse;
use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class UsageRecorder
{
    private const MONTHLY_BUDGET_CACHE_PREFIX = 'ai.usage.monthly.';
    private const BUDGET_CACHE_TTL = 3600; // 1 hour

    public function record(
        int $tenantId,
        string $passType,
        LlmResponse $response,
        ?int $conversationId = null,
    ): void {
        try {
            $costMicros = $this->estimateCostMicros($response);
            AiUsageLog::create([
                'user_id'         => $tenantId,
                'conversation_id' => $conversationId,
                'pass_type'       => $passType,
                'model'           => $response->model,
                'provider'        => $response->provider,
                'tokens_in'       => $response->tokensIn,
                'tokens_out'      => $response->tokensOut,
                'latency_ms'      => $response->latencyMs,
                'cost_micros'     => $costMicros,
                'success'         => $response->success,
                'error_code'      => $response->errorCode,
            ]);

            // Bust the monthly usage cache
            Cache::forget(self::MONTHLY_BUDGET_CACHE_PREFIX . $tenantId . '.' . now()->format('Y-m'));
        } catch (\Throwable $e) {
            Log::warning('ai.usage_recorder.failed', ['error' => $e->getMessage(), 'tenant' => $tenantId]);
        }
    }

    public function monthlyTokensUsed(int $tenantId): int
    {
        $cacheKey = self::MONTHLY_BUDGET_CACHE_PREFIX . $tenantId . '.' . now()->format('Y-m');
        return (int) Cache::remember($cacheKey, self::BUDGET_CACHE_TTL, function () use ($tenantId) {
            return AiUsageLog::query()
                ->where('user_id', $tenantId)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->sum(DB::raw('tokens_in + tokens_out'));
        });
    }

    public function exceedsBudget(int $tenantId, int $monthlyTokenLimit): bool
    {
        if ($monthlyTokenLimit <= 0) {
            return false;
        }
        return $this->monthlyTokensUsed($tenantId) >= $monthlyTokenLimit;
    }

    /** Alias of exceedsBudget — used by the agent Employee. */
    public function isBudgetExceeded(int $tenantId, int $monthlyTokenLimit): bool
    {
        return $this->exceedsBudget($tenantId, $monthlyTokenLimit);
    }

    /**
     * Record raw token usage without an LlmResponse object.
     * Used by the agent loop which accumulates tokens across multiple steps.
     */
    public function recordRaw(
        int     $tenantId,
        string  $passType,
        int     $tokensIn,
        int     $tokensOut,
        int     $latencyMs,
        string  $model,
        ?int    $conversationId = null,
    ): void {
        try {
            $fakeResponse = new \App\Domain\Ai\DTOs\LlmResponse(
                content:    '',
                tokensIn:   $tokensIn,
                tokensOut:  $tokensOut,
                latencyMs:  $latencyMs,
                model:      $model,
                provider:   'agent',
                success:    true,
            );
            $costMicros = $this->estimateCostMicros($fakeResponse);

            AiUsageLog::create([
                'user_id'         => $tenantId,
                'conversation_id' => $conversationId,
                'pass_type'       => $passType,
                'model'           => $model,
                'provider'        => 'agent',
                'tokens_in'       => $tokensIn,
                'tokens_out'      => $tokensOut,
                'latency_ms'      => $latencyMs,
                'cost_micros'     => $costMicros,
                'success'         => true,
            ]);

            Cache::forget(self::MONTHLY_BUDGET_CACHE_PREFIX . $tenantId . '.' . now()->format('Y-m'));
        } catch (\Throwable $e) {
            Log::warning('ai.usage_recorder.recordRaw.failed', ['error' => $e->getMessage(), 'tenant' => $tenantId]);
        }
    }

    private function estimateCostMicros(LlmResponse $response): int
    {
        // Rough cost per million tokens in micros (millionths of USD)
        $rates = [
            'gpt-5-nano'             => ['in' => 50,   'out' => 400],
            'gpt-5-mini'             => ['in' => 250,  'out' => 2000],
            'gpt-5'                  => ['in' => 1250, 'out' => 10000],
            'gpt-4o-mini'            => ['in' => 150,  'out' => 600],
            'gpt-4o'                 => ['in' => 2500, 'out' => 10000],
            'claude-haiku-4-5'       => ['in' => 1000, 'out' => 5000],
            'gemini-2.0-flash'       => ['in' => 150,  'out' => 600],
            'deepseek-chat'          => ['in' => 140,  'out' => 280],
            'text-embedding-3-small' => ['in' => 20,   'out' => 0],
        ];

        foreach ($rates as $modelKey => $rate) {
            if (str_contains($response->model, $modelKey)) {
                return (int) (
                    ($response->tokensIn / 1_000_000) * $rate['in'] +
                    ($response->tokensOut / 1_000_000) * $rate['out']
                );
            }
        }

        // Default: assume mid-range
        return (int) (($response->tokensIn + $response->tokensOut) / 1_000_000 * 500);
    }
}
