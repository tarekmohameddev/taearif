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
 * We correlate by TAEARIF_CALL_ID (top-level, ChanVariable, or inherited
 * __TAEARIF_CALL_ID), then by Uniqueid / Linkedid once stored.
 */
class AmiListenCommand extends Command
{
    protected $signature   = 'calling:ami-listen';
    protected $description = 'Listen to Asterisk AMI events and update call_logs in real time.';

    private const HEARTBEAT_KEY    = 'calling:ami-listen:heartbeat';
    private const HEARTBEAT_EVERY  = 15; // seconds
    private const RECONNECT_DELAYS = [2, 4, 8, 16, 30, 60]; // backoff
    private const PENDING_TTL      = 120; // seconds to hold Hangup until OriginateResponse maps Uniqueid
    private const BUFFERED_EVENTS  = [
        'Hangup', 'Dial', 'DialBegin', 'DialEnd',
        'BridgeEnter', 'Newchannel', 'Newstate', 'OriginateResponse',
    ];

    /** @var array<string, list<array{at:int, event:array<string, string>}>> */
    private array $pendingByUniqueid = [];

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
        if ($eventName === '') {
            return;
        }

        if (in_array($eventName, self::BUFFERED_EVENTS, true)) {
            $this->logAmi($event, 'received');
        }

        // OriginateResponse carries the channel name but its call ID is in ActionID,
        // not in a channel variable. Extract it separately before the main flow.
        if ($eventName === 'OriginateResponse') {
            $this->handleOriginateResponse($event);
            return;
        }

        // PBX filters VarSet (`eventfilter=!Event: VarSet`) — keep handler if that changes.
        if ($eventName === 'VarSet') {
            $this->handleVarSet($event);
            return;
        }

        $log = $this->resolveCallLog($event);
        if (!$log) {
            $this->rememberPending($event);
            return;
        }

        if ($log->isTerminal()) {
            return;
        }

        $this->applyCallEvent($log, $event);
    }

    /**
     * @param  array<string, string>  $event
     */
    private function applyCallEvent(CallLog $log, array $event): void
    {
        $eventName = $event['Event'] ?? '';

        $this->persistAsteriskIdentifiers($log, $event);
        $this->appendEvent($log, $eventName, $event);

        $newStatus = $this->mapEventToStatus($eventName, $event, $log);
        if ($newStatus && $newStatus !== $log->status) {
            $this->applyStatusTransition($log, $newStatus, $event);
        }
    }

    /**
     * Capture channel + Uniqueid from the OriginateResponse AMI event.
     *
     * ActionID format: "orig-{uuid}" (set in AmiClient::originate()).
     * Channel is the originated PJSIP name used for targeted hangup.
     * Uniqueid is Asterisk's stable call id (e.g. 1787700790.33).
     */
    private function handleOriginateResponse(array $event): void
    {
        $actionId = $event['ActionID'] ?? '';
        if (!str_starts_with($actionId, 'orig-')) {
            $this->rememberPending($event);
            return;
        }

        $callId = substr($actionId, 5); // strip "orig-" prefix
        if (strlen($callId) !== 36) {
            return;
        }

        $log = CallLog::find($callId);
        if (!$log) {
            $this->rememberPending($event);
            return;
        }

        $this->persistAsteriskIdentifiers($log, $event, captureChannel: true);

        if (!$log->isTerminal()) {
            $this->appendEvent($log, 'OriginateResponse', $event);
        }

        $this->replayPending($log->fresh() ?? $log);
    }

    private function handleVarSet(array $event): void
    {
        $callId = $this->extractCallId($event);
        if (!$callId) {
            return;
        }

        $log = CallLog::find($callId);
        if (!$log || $log->isTerminal()) {
            return;
        }

        $this->persistAsteriskIdentifiers($log, $event);
    }

    /**
     * @param  array<string, string>  $event
     */
    private function resolveCallLog(array $event): ?CallLog
    {
        $callId = $this->extractCallId($event);
        if ($callId) {
            $log = CallLog::find($callId);
            if ($log) {
                return $log;
            }
        }

        $ids = array_values(array_unique(array_filter([
            $event['Uniqueid'] ?? null,
            $event['Linkedid'] ?? null,
        ], fn ($id) => is_string($id) && $id !== '')));

        if ($ids === []) {
            return null;
        }

        return CallLog::query()
            ->whereIn('asterisk_uniqueid', $ids)
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * @param  array<string, string>  $event
     */
    private function extractCallId(array $event): ?string
    {
        foreach (['TAEARIF_CALL_ID', '__TAEARIF_CALL_ID', '_TAEARIF_CALL_ID'] as $key) {
            $value = $this->trimAmi($event[$key] ?? null);
            if ($this->isUuid($value)) {
                return $value;
            }
        }

        $userField = $this->trimAmi($event['UserField'] ?? null);
        if ($this->isUuid($userField)) {
            return $userField;
        }

        $variable = $event['Variable'] ?? null;
        if (!is_string($variable) || $variable === '') {
            return null;
        }

        if ($this->isUuid($variable)) {
            return $variable;
        }

        // VarSet: Variable is the name, Value is the UUID.
        if (in_array($variable, ['TAEARIF_CALL_ID', '__TAEARIF_CALL_ID', '_TAEARIF_CALL_ID'], true)
            && $this->isUuid($event['Value'] ?? null)
        ) {
            return $event['Value'];
        }

        // Originate Variable: TAEARIF_CALL_ID=uuid,__TAEARIF_CALL_ID=uuid,...
        if (str_contains($variable, '=')) {
            foreach (explode(',', $variable) as $pair) {
                [$k, $v] = array_pad(explode('=', $pair, 2), 2, '');
                if (in_array($k, ['TAEARIF_CALL_ID', '__TAEARIF_CALL_ID', '_TAEARIF_CALL_ID'], true)
                    && $this->isUuid($v)
                ) {
                    return $v;
                }
            }
        }

        return null;
    }

    private function trimAmi(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value, " \t\"'");
        return $value === '' ? null : $value;
    }

    private function isUuid(mixed $value): bool
    {
        $value = $this->trimAmi($value);
        return $value !== null && strlen($value) === 36;
    }

    /**
     * Persist Uniqueid and Channel once. Never overwrite with a B-leg Uniqueid.
     *
     * Channel example: PJSIP/agent_1430_1430-00000021
     *
     * @param  array<string, string>  $event
     */
    private function persistAsteriskIdentifiers(CallLog $log, array $event, bool $captureChannel = false): void
    {
        $updates = [];

        $uniqueid = $this->trimAmi($event['Uniqueid'] ?? null) ?? '';
        $linkedid = $this->trimAmi($event['Linkedid'] ?? null) ?? '';
        $isMasterLeg = $uniqueid !== '' && ($linkedid === '' || $linkedid === $uniqueid);
        $hasALegCallId = $this->isUuid($event['TAEARIF_CALL_ID'] ?? null);

        $channel = $this->trimAmi($event['Channel'] ?? null);
        if (!$log->asterisk_channel && $channel
            && ($captureChannel || $hasALegCallId || $isMasterLeg)
        ) {
            $updates['asterisk_channel'] = $channel;
        }

        if (!$log->asterisk_uniqueid) {
            if ($isMasterLeg) {
                $updates['asterisk_uniqueid'] = $uniqueid;
            } elseif ($linkedid !== '') {
                $updates['asterisk_uniqueid'] = $linkedid;
            } elseif ($uniqueid !== '') {
                $updates['asterisk_uniqueid'] = $uniqueid;
            }
        }

        if ($updates === []) {
            return;
        }

        $log->update($updates);

        $message = sprintf(
            '[ami-listen] mapped call %s uniqueid=%s channel=%s',
            $log->id,
            $log->asterisk_uniqueid ?? '-',
            $log->asterisk_channel ?? '-'
        );
        $this->emit($message);
    }

    /**
     * Hangup often arrives before OriginateResponse. Hold it until Uniqueid is mapped.
     *
     * @param  array<string, string>  $event
     */
    private function rememberPending(array $event): void
    {
        $name = $event['Event'] ?? '';
        if (!in_array($name, self::BUFFERED_EVENTS, true)) {
            return;
        }

        $this->logAmi($event, 'unmapped');

        foreach ([$event['Uniqueid'] ?? null, $event['Linkedid'] ?? null] as $id) {
            $id = $this->trimAmi($id);
            if ($id === null) {
                continue;
            }
            $this->pendingByUniqueid[$id][] = ['at' => time(), 'event' => $event];
        }

        $this->prunePending();
    }

    private function replayPending(CallLog $log): void
    {
        $keys = array_values(array_filter([
            $this->trimAmi($log->asterisk_uniqueid),
        ]));

        $queue = [];
        foreach ($keys as $key) {
            if (!empty($this->pendingByUniqueid[$key])) {
                foreach ($this->pendingByUniqueid[$key] as $item) {
                    $queue[] = $item['event'];
                }
                unset($this->pendingByUniqueid[$key]);
            }
        }

        foreach ($queue as $event) {
            $name = $event['Event'] ?? '';
            if ($name === '' || $name === 'OriginateResponse' || $name === 'VarSet') {
                continue;
            }
            $log->refresh();
            if ($log->isTerminal()) {
                return;
            }
            $this->applyCallEvent($log, $event);
        }
    }

    private function prunePending(): void
    {
        $cutoff = time() - self::PENDING_TTL;
        foreach ($this->pendingByUniqueid as $id => $items) {
            $kept = array_values(array_filter($items, fn ($item) => $item['at'] >= $cutoff));
            if ($kept === []) {
                unset($this->pendingByUniqueid[$id]);
            } else {
                $this->pendingByUniqueid[$id] = $kept;
            }
        }
    }

    /**
     * @param  array<string, string>  $event
     */
    private function logAmi(array $event, string $tag): void
    {
        $this->emit(sprintf(
            '[ami-listen] %s Event=%s Uniqueid=%s Linkedid=%s Channel=%s ActionID=%s Cause=%s TAEARIF_CALL_ID=%s',
            $tag,
            $event['Event'] ?? '-',
            $event['Uniqueid'] ?? '-',
            $event['Linkedid'] ?? '-',
            $event['Channel'] ?? '-',
            $event['ActionID'] ?? '-',
            $event['Cause'] ?? '-',
            $event['TAEARIF_CALL_ID'] ?? $event['__TAEARIF_CALL_ID'] ?? '-'
        ));
    }

    private function emit(string $message): void
    {
        if ($this->output) {
            $this->info($message);
        }
        Log::channel('daily')->info($message);
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
