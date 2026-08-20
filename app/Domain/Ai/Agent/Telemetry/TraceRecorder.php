<?php

declare(strict_types=1);

namespace App\Domain\Ai\Agent\Telemetry;

use App\Models\AiTurnTrace;
use Illuminate\Support\Facades\Log;

/**
 * Persists TurnTrace to the ai_turn_traces table.
 *
 * Failures are caught and logged — a telemetry write must never break delivery.
 */
final class TraceRecorder
{
    public function record(TurnTrace $trace): ?AiTurnTrace
    {
        try {
            return AiTurnTrace::create([
                'user_id'           => $trace->tenantId,
                'conversation_id'   => $trace->conversationId,
                'trigger_message_id'=> $trace->triggerMessageId,
                'idempotency_key'   => $trace->idempotencyKey,
                'brief_before'      => $trace->briefBefore,
                'brief_after'       => $trace->briefAfter,
                'steps'             => $trace->steps,
                'tool_call_log'     => $trace->toolCallLog,
                'guard_violations'  => $trace->guardViolations,
                'model'             => $trace->model,
                'tokens_in'         => $trace->tokensIn,
                'tokens_out'        => $trace->tokensOut,
                'latency_ms'        => $trace->latencyMs,
                'decision'          => $trace->decision,
                'rendered_reply'    => $trace->renderedReply,
                'delivery_status'   => $trace->deliveryStatus,
                'cassette_key'      => $trace->cassetteKey,
            ]);
        } catch (\Throwable $e) {
            Log::error('agent.trace_recorder.failed', [
                'conversation_id'   => $trace->conversationId,
                'trigger_message_id'=> $trace->triggerMessageId,
                'error'             => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Mark delivery outcome after WhatsApp send completes/fails.
     */
    public function markDelivery(int $traceId, string $status): void
    {
        try {
            AiTurnTrace::where('id', $traceId)->increment('delivery_attempts', 1, [
                'delivery_status' => $status,
            ]);
        } catch (\Throwable $e) {
            Log::warning('agent.trace_recorder.mark_delivery_failed', [
                'trace_id' => $traceId,
                'status'   => $status,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
