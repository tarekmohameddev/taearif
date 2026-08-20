<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\DTOs;

/**
 * Result of one agent loop step.
 *
 * Exactly one of $toolCalls or $finalReply will be non-null.
 */
final class AgentStepResult
{
    /**
     * @param ToolCall[]|null $toolCalls  Non-null when the model wants to call tools.
     * @param array<string,mixed>|null $finalReply Decoded structured reply when the model is done.
     */
    public function __construct(
        public readonly ?array $toolCalls,
        public readonly ?array $finalReply,
        public readonly int    $tokensIn,
        public readonly int    $tokensOut,
        public readonly int    $latencyMs,
        public readonly string $model,
        public readonly string $provider,
    ) {}

    public function wantsToolCall(): bool
    {
        return $this->toolCalls !== null && count($this->toolCalls) > 0;
    }

    public function hasFinalReply(): bool
    {
        return $this->finalReply !== null;
    }
}
