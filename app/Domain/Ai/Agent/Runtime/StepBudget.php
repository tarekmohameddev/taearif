<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Runtime;

/**
 * Tracks resource consumption for a single agent turn.
 *
 * Exhaustion triggers when any one of three ceilings is crossed:
 *  - max tool steps (prevents infinite loops)
 *  - max COMPLETION tokens (model output only — NOT prompt tokens)
 *  - wall-clock timeout
 *
 * Prompt tokens are re-sent on every step and grow with conversation length.
 * Including them in the exhaustion check caused budget_exhausted after just
 * 1–2 steps for long conversations (RC2 fix: track separately).
 * Prompt tokens are still logged for cost accounting but do not trigger exhaustion.
 *
 * `stepsBeforeForce` is the step count at which the loop should issue a forced
 * finalize (tools=[], tool_choice=none) so the model must produce a final reply.
 */
final class StepBudget
{
    private int $stepsUsed        = 0;
    private int $promptTokens     = 0;
    private int $completionTokens = 0;
    private int $startMs;

    /** @var array<int,array{step:int,tokens_in:int,tokens_out:int,latency_ms:int}> */
    private array $log = [];

    /**
     * @param int $maxSteps           Max tool-call steps before forcing finalization.
     * @param int $maxCompletionTokens Ceiling on model-generated output tokens only.
     * @param int $wallClockMs        Hard wall-clock limit in milliseconds.
     */
    public function __construct(
        private readonly int $maxSteps            = 6,
        private readonly int $maxCompletionTokens = PHP_INT_MAX,
        private readonly int $wallClockMs         = 50_000,
    ) {
        $this->startMs = (int) round(microtime(true) * 1000);
    }

    public function recordStep(int $tokensIn, int $tokensOut, int $latencyMs): void
    {
        $this->stepsUsed        += 1;
        $this->promptTokens     += $tokensIn;
        $this->completionTokens += $tokensOut;
        $this->log[]             = [
            'step'       => $this->stepsUsed,
            'tokens_in'  => $tokensIn,
            'tokens_out' => $tokensOut,
            'latency_ms' => $latencyMs,
        ];
    }

    /** True when the budget is fully spent — loop must stop. */
    public function isExhausted(): bool
    {
        return $this->stepsUsed >= $this->maxSteps
            || $this->completionTokens >= $this->maxCompletionTokens
            || $this->elapsedMs() >= $this->wallClockMs;
    }

    /** True when only one step remains — use to trigger forced finalization. */
    public function onLastStep(): bool
    {
        return $this->stepsUsed === $this->maxSteps - 1;
    }

    public function stepsUsed(): int        { return $this->stepsUsed; }
    public function promptTokens(): int     { return $this->promptTokens; }
    public function completionTokens(): int { return $this->completionTokens; }

    /** Total tokens logged (prompt + completion) — used for usage recording. */
    public function tokensUsed(): int { return $this->promptTokens + $this->completionTokens; }

    public function elapsedMs(): int { return (int) round(microtime(true) * 1000) - $this->startMs; }

    public function maxSteps(): int { return $this->maxSteps; }

    /** @return array<int,array{step:int,tokens_in:int,tokens_out:int,latency_ms:int}> */
    public function log(): array { return $this->log; }
}
