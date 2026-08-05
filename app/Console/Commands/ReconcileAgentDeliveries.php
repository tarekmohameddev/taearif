<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Ai\Agent\Telemetry\TraceRecorder;
use App\Models\AiTurnTrace;
use App\Models\WaAiConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reconcile stuck delivery records in ai_turn_traces.
 *
 * Records in `pending` status older than 60 seconds either had a network error
 * during send or the process was interrupted.  This command re-attempts delivery
 * or escalates to `failed` after the attempt ceiling is reached.
 *
 * Scheduled every minute in Console\Kernel.
 */
final class ReconcileAgentDeliveries extends Command
{
    protected $signature = 'ai:agent:reconcile-deliveries
        {--age=60 : Min age in seconds before a pending record is considered stuck}
        {--max-attempts=3 : Max delivery attempts before marking as failed}
        {--limit=50 : Max records to process per run}';

    protected $description = 'Re-attempt delivery for stuck ai_turn_traces records.';

    public function __construct(
        private readonly TraceRecorder $traceRecorder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ageSeconds  = (int) $this->option('age');
        $maxAttempts = (int) $this->option('max-attempts');
        $limit       = (int) $this->option('limit');

        $stuck = AiTurnTrace::where('delivery_status', 'pending')
            ->where('created_at', '<', now()->subSeconds($ageSeconds))
            ->where('delivery_attempts', '<', $maxAttempts)
            ->whereNotNull('rendered_reply')
            ->limit($limit)
            ->get();

        if ($stuck->isEmpty()) {
            return Command::SUCCESS;
        }

        $this->info("Reconciling {$stuck->count()} stuck delivery record(s)...");

        foreach ($stuck as $trace) {
            try {
                // Attempt re-delivery via the communication service
                $redelivered = $this->redeliver($trace);

                $newStatus = $redelivered ? 'sent' : 'failed';
                $this->traceRecorder->markDelivery($trace->id, $newStatus);

                $this->line("Trace #{$trace->id}: {$newStatus}");
            } catch (\Throwable $e) {
                Log::error('agent.reconciler.failed', [
                    'trace_id' => $trace->id,
                    'error'    => $e->getMessage(),
                ]);
                // Increment attempt count
                AiTurnTrace::where('id', $trace->id)->increment('delivery_attempts');
            }
        }

        // Mark any records that have exhausted attempts as failed
        AiTurnTrace::where('delivery_status', 'pending')
            ->where('delivery_attempts', '>=', $maxAttempts)
            ->where('created_at', '<', now()->subSeconds($ageSeconds))
            ->update(['delivery_status' => 'failed']);

        return Command::SUCCESS;
    }

    private function redeliver(AiTurnTrace $trace): bool
    {
        // Re-delivery is best-effort: we log the intent and return false so the
        // status is set to 'failed' for human review.  A full re-delivery would
        // require re-constructing the HumanCadence context (waNumberId, toPhone)
        // which is not stored in ai_turn_traces (by design — the rendered reply IS
        // stored, but the delivery channel metadata is in conversations/messages).
        // Future improvement: store waNumberId in ai_turn_traces and fully re-send.
        Log::warning('agent.reconciler.redeliver_needed', [
            'trace_id'        => $trace->id,
            'conversation_id' => $trace->conversation_id,
            'rendered_reply'  => substr((string) $trace->rendered_reply, 0, 100),
            'attempts'        => $trace->delivery_attempts,
        ]);
        return false;
    }
}
