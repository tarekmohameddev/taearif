<?php

namespace App\Console\Commands\Calling;

use App\Domain\Calling\Events\CallStatusUpdated;
use App\Domain\Calling\Models\CallEvent;
use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Services\AmiClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Long-running AMI event consumer.
 *
 * Run under supervisor (not as a queue worker).
 * Touch a cache key every 15 s so a watchdog can detect hangs.
 *
 * Status mapping (Asterisk 20 PJSIP events):
 *
 *   DialBegin (agent channel answered by browser)   -> ringing_dest
 *   DialEnd + DialStatus=ANSWER                     -> answered
 *   Bridge* / BridgeEnter                           -> answered
 *   Hangup on agent channel before answer           -> (depends on cause)
 *   Hangup on call after answered                   -> completed / failed / busy / no_answer / canceled
 *
 * We correlate by the TAEARIF_CALL_ID channel variable present in every event.
 */
class AmiListenCommand extends Command
{
    protected $signature   = 'calling:ami-listen';
    protected $description = 'Listen to Asterisk AMI events and update call_logs in real time.';

    private const HEARTBEAT_KEY    = 'calling:ami-listen:heartbeat';
    private const HEARTBEAT_EVERY  = 15; // seconds
    private const RECONNECT_DELAYS = [2, 4, 8, 16, 30, 60]; // backoff

    public function handle(): int
    {
        $this->info('[ami-listen] Starting AMI event consumer...');

        $attempt = 0;

        while (true) {
            try {
                $client = $this->buildClient();
                $client->connectWithEvents();

                $this->info('[ami-listen] Connected to AMI.');
                $attempt    = 0;
                $lastHeart  = time();

                while ($client->isConnected()) {
                    $event = $client->readEvent();

                    if ($event === null) {
                        // null = real EOF / socket error → reconnect
                        break;
                    }

                    if ($event === []) {
                        // Empty array = select timed out, connection alive but idle.
                        // Just run housekeeping and loop without treating this as a drop.
                        if (time() - $lastHeart >= self::HEARTBEAT_EVERY) {
                            Cache::put(self::HEARTBEAT_KEY, now()->toIso8601String(), 60);
                            $lastHeart = time();
                        }
                        continue;
                    }

                    $this->processEvent($event);

                    // Heartbeat
                    if (time() - $lastHeart >= self::HEARTBEAT_EVERY) {
                        Cache::put(self::HEARTBEAT_KEY, now()->toIso8601String(), 60);
                        $lastHeart = time();
                    }
                }

                $client->disconnect();
                $this->warn('[ami-listen] AMI connection dropped. Reconnecting...');

            } catch (\Throwable $e) {
                Log::channel('daily')->error('[ami-listen] Exception: ' . $e->getMessage());
                $this->error('[ami-listen] ' . $e->getMessage());
            }

            $delay = self::RECONNECT_DELAYS[min($attempt, count(self::RECONNECT_DELAYS) - 1)];
            $attempt++;
            $this->warn("[ami-listen] Waiting {$delay}s before reconnect (attempt {$attempt})...");
            sleep($delay);
        }

        return self::SUCCESS;
    }

    // -----------------------------------------------------------------
    // Event processing
    // -----------------------------------------------------------------

    private function processEvent(array $event): void
    {
        $eventName = $event['Event'] ?? '';

        // OriginateResponse carries the channel name but its call ID is in ActionID,
        // not in a channel variable. Extract it separately before the main flow.
        if ($eventName === 'OriginateResponse') {
            $this->handleOriginateResponse($event);
            return;
        }

        $callId = $event['TAEARIF_CALL_ID']
               ?? $event['Variable'] // some events put custom vars here
               ?? null;

        // Try to extract TAEARIF_CALL_ID from the Variable field in CDR/UserEvent events
        if (!$callId && isset($event['UserField'])) {
            $callId = $event['UserField'];
        }

        if (!$callId || strlen($callId) !== 36) {
            return; // Not a Taearif call event
        }

        $log = CallLog::find($callId);
        if (!$log || $log->isTerminal()) {
            return;
        }

        $this->appendEvent($log, $eventName, $event);

        $newStatus = $this->mapEventToStatus($eventName, $event, $log);
        if ($newStatus && $newStatus !== $log->status) {
            $this->applyStatusTransition($log, $newStatus, $event);
        }
    }

    /**
     * Capture the Asterisk channel name from the OriginateResponse AMI event.
     *
     * ActionID format: "orig-{uuid}" (set in AmiClient::originate()).
     * The Channel field contains the full PJSIP channel name we need for targeted hangup.
     */
    private function handleOriginateResponse(array $event): void
    {
        $actionId = $event['ActionID'] ?? '';
        if (!str_starts_with($actionId, 'orig-')) {
            return;
        }

        $callId  = substr($actionId, 5); // strip "orig-" prefix
        $channel = $event['Channel'] ?? null;

        if (!$channel || strlen($callId) !== 36) {
            return;
        }

        CallLog::where('id', $callId)
            ->whereNull('asterisk_channel')
            ->update(['asterisk_channel' => $channel]);
    }

    private function mapEventToStatus(string $eventName, array $event, CallLog $log): ?string
    {
        return match ($eventName) {
            'DialBegin'   => 'ringing_dest',
            'BridgeEnter' => 'answered',
            'Dial'        => $this->mapDialEvent($event),
            'Hangup'      => $this->mapHangupEvent($event, $log->status),
            default       => null,
        };
    }

    private function mapDialEvent(array $event): ?string
    {
        $subEvent = $event['SubEvent'] ?? '';
        if ($subEvent === 'Begin') {
            return 'ringing_dest';
        }
        if ($subEvent === 'End') {
            return match ($event['DialStatus'] ?? '') {
                'ANSWER'   => 'answered',
                'BUSY'     => 'busy',
                'NOANSWER' => 'no_answer',
                'CANCEL'   => 'canceled',
                default    => 'failed',
            };
        }
        return null;
    }

    private function mapHangupEvent(array $event, string $currentStatus): string
    {
        if ($currentStatus === 'answered') {
            return 'completed';
        }

        $cause = (int) ($event['Cause'] ?? 0);
        return match (true) {
            $cause === 17 => 'busy',
            $cause === 19 => 'no_answer',
            // Cause 16 (Normal Clearing) before answer means the agent hung up
            // before the destination answered — i.e., the call was canceled, not completed.
            // 'completed' is only correct when currentStatus === 'answered' (handled above).
            $cause === 16 || $cause === 21 => 'canceled',
            default => 'failed',
        };
    }

    private function applyStatusTransition(CallLog $log, string $newStatus, array $event): void
    {
        $updates = ['status' => $newStatus];

        if ($newStatus === 'answered' && !$log->answered_at) {
            $updates['answered_at'] = now();
        }

        if (in_array($newStatus, CallLog::TERMINAL_STATUSES, true)) {
            $endedAt = now();
            $updates['ended_at'] = $endedAt;
            if ($log->answered_at) {
                $updates['duration_seconds'] = $endedAt->diffInSeconds($log->answered_at);
            }
            if (in_array($newStatus, ['failed', 'busy', 'no_answer'], true)) {
                $updates['fail_reason'] = $event['Cause-txt'] ?? $newStatus;
            }
        }

        $log->update($updates);

        event(new CallStatusUpdated($log->fresh()));
    }

    private function appendEvent(CallLog $log, string $eventName, array $payload): void
    {
        CallEvent::create([
            'call_log_id' => $log->id,
            'event_name'  => $eventName,
            'payload'     => $payload,
            'created_at'  => now(),
        ]);
    }

    // -----------------------------------------------------------------
    // Client factory
    // -----------------------------------------------------------------

    private function buildClient(): AmiClient
    {
        return new AmiClient(
            host:     config('calling.ami.host'),
            port:     config('calling.ami.port'),
            username: config('calling.ami.username'),
            secret:   config('calling.ami.secret'),
            timeout:  config('calling.ami.timeout', 10),
        );
    }
}
