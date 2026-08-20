<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Telemetry;

/**
 * Immutable snapshot of one agent turn, ready to persist to ai_turn_traces.
 */
final class TurnTrace
{
    /**
     * @param  array<string,mixed>      $briefBefore      CustomerBrief facts before the turn.
     * @param  array<string,mixed>      $briefAfter       CustomerBrief facts after applying brief_updates.
     * @param  array<int,array>         $steps            Loop steps (tool calls + final reply).
     * @param  array<string,mixed>[]    $toolCallLog      Compact log of every tool call + result.
     * @param  string[]                 $guardViolations  CitationGuard violation messages (if any).
     */
    public function __construct(
        public readonly int     $tenantId,
        public readonly int     $conversationId,
        public readonly int     $triggerMessageId,
        public readonly string  $idempotencyKey,
        public readonly array   $briefBefore,
        public readonly array   $briefAfter,
        public readonly array   $steps,
        public readonly array   $toolCallLog,
        public readonly array   $guardViolations,
        public readonly string  $model,
        public readonly int     $tokensIn,
        public readonly int     $tokensOut,
        public readonly int     $latencyMs,
        public readonly string  $decision,           // delivered|shadow|handoff|skipped|failed
        public readonly ?string $renderedReply,
        public readonly string  $deliveryStatus,     // pending|sent|delivered|failed
        public readonly string  $cassetteKey,
    ) {}
}
