<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Runtime;

/**
 * The output of one agent loop run.
 */
final class AgentLoopResult
{
    /**
     * @param  array<string,mixed>|null $finalReply   Decoded structured reply, or null on failure.
     * @param  array<int,array>         $toolCallLog  All tool calls made during the loop.
     * @param  array<int,array>         $steps        Full step trace for telemetry.
     * @param  string|null              $failureReason  Non-null means the loop failed.
     */
    public function __construct(
        public readonly ?array    $finalReply,
        public readonly array     $toolCallLog,
        public readonly array     $steps,
        public readonly ?string   $failureReason,
        public readonly StepBudget $budget,
    ) {}

    public function succeeded(): bool
    {
        return $this->finalReply !== null;
    }

    public function failed(): bool
    {
        return !$this->succeeded();
    }
}
