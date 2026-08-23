<?php

declare(strict_types=1);

namespace App\Domain\Calling\Services;

use App\Domain\Calling\Contracts\AmiClientInterface;
use App\Domain\Calling\DTOs\AmiOriginateDto;
use App\Domain\Calling\Exceptions\AmiException;
use App\Domain\Calling\Exceptions\CallingModuleDisabledException;
use App\Domain\Calling\Exceptions\InvalidPhoneNumberException;
use App\Domain\Calling\Exceptions\NoAgentExtensionException;
use App\Domain\Calling\Exceptions\NoAvailableLineException;
use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallLog;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Models\CallSimLine;
use App\Models\ApiCustomer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CallOriginationService
{
    public function __construct(
        private readonly AmiClientInterface $ami,
        private readonly PhoneNumberService $phone,
    ) {}

    /**
     * Place an outbound call from the CRM "Call" button.
     *
     * @throws CallingModuleDisabledException
     * @throws InvalidPhoneNumberException
     * @throws NoAgentExtensionException
     * @throws NoAvailableLineException
     */
    public function placeCall(
        User $agent,
        ?int $customerId = null,
        ?string $toRaw = null,
        ?int $simLineId = null
    ): CallLog {
        $tenantId = $agent->tenantOwnerId();

        // 1. Calling module must be enabled for this tenant
        $settings = CallSetting::where('tenant_id', $tenantId)->first();
        if (!$settings || !$settings->enabled) {
            throw new CallingModuleDisabledException();
        }

        // 2. Validate customer_id tenant ownership BEFORE using it, even when a
        //    raw phone number is also provided. An unvalidated customer_id stored
        //    in call_logs would expose another tenant's customer data via eager-load.
        if ($customerId !== null) {
            $owned = ApiCustomer::where('id', $customerId)
                ->where('user_id', $tenantId)
                ->exists();
            if (!$owned) {
                $customerId = null;
            }
        }

        // 3. Resolve the destination number
        $rawPhone = $this->resolveRawPhone($toRaw, $customerId, $tenantId);
        $e164     = $this->phone->toE164($rawPhone);

        // 4. Require an active SIP extension for the agent
        $ext = CallAgentExtension::where('tenant_id', $tenantId)
            ->where('user_id', $agent->id)
            ->where('is_active', true)
            ->first();
        if (!$ext) {
            throw new NoAgentExtensionException();
        }

        // 5. Pick a SIM line (explicit or auto-select)
        $line = $this->resolveLine($tenantId, $simLineId, $agent->id);

        // 6 + 7. Enforce max_channels and create the call log inside a single transaction.
        //
        // The call_settings row is locked with SELECT … FOR UPDATE so that concurrent
        // placeCall requests for the same tenant are serialised at the DB level.
        // Without the lock, two simultaneous requests could both read the same active
        // call count, both pass the limit check, and both insert — exceeding max_channels.
        // The second request will block here until the first transaction commits, then
        // re-reads an already-incremented count and fails if the limit is now reached.
        $callId = (string) Str::uuid();

        $log = DB::transaction(function () use (
            $callId, $tenantId, $customerId, $agent, $line, $e164, $settings, $ext
        ) {
            // Lock the settings row to serialise concurrent placeCall requests for this tenant.
            $lockedSettings = CallSetting::where('tenant_id', $tenantId)
                ->lockForUpdate()
                ->first();

            if (!$lockedSettings || !$lockedSettings->enabled) {
                throw new CallingModuleDisabledException();
            }

            $activeCalls = CallLog::where('tenant_id', $tenantId)
                ->whereNotIn('status', CallLog::TERMINAL_STATUSES)
                ->count();

            if ($activeCalls >= $lockedSettings->max_channels) {
                throw new NoAvailableLineException(
                    "Maximum concurrent channels ({$lockedSettings->max_channels}) reached."
                );
            }

            $log = CallLog::create([
                'id'         => $callId,
                'tenant_id'  => $tenantId,
                'customer_id'=> $customerId,
                'user_id'    => $agent->id,
                'trunk_id'   => $line->trunk_id,
                'sim_line_id'=> $line->id,
                'direction'  => 'outbound',
                'to_e164'    => $e164,
                'from_e164'  => $line->msisdn,
                'status'     => 'initiated',
            ]);

            $dto = new AmiOriginateDto(
                callId:          $callId,
                sipUsername:     $ext->sip_username,
                context:         $ext->asterisk_context,
                destDialString:  $this->phone->toDialString($e164),
                trunkEndpoint:   $line->asterisk_endpoint,
                callerIdE164:    $line->msisdn,
                record:          $lockedSettings->record_by_default,
                ringTimeoutMs:   config('calling.originate.ring_timeout_ms', 30000),
            );

            $this->ami->originate($dto);

            return $log;
        });

        return $log;
    }

    /**
     * Request a hangup for an in-progress call.
     *
     * The channel name is stored in call_logs.asterisk_channel once the
     * OriginateResponse AMI event is processed by the ami-listen daemon.
     * If the channel has not been captured yet (very fast hangup before the
     * daemon processes the response), an AmiException is thrown so the
     * caller can surface a "try again in a moment" message.
     *
     * @throws AmiException
     */
    public function hangup(string $callId, User $requester): void
    {
        $tenantId = $requester->tenantOwnerId();
        $log      = CallLog::where('id', $callId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        if ($log->isTerminal()) {
            return;
        }

        if (empty($log->asterisk_channel)) {
            throw new AmiException(
                'Channel not yet assigned — the call is still being set up. Please try again in a moment.'
            );
        }

        $this->ami->hangup($log->asterisk_channel);
    }

    // -----------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------

    private function resolveRawPhone(?string $toRaw, ?int $customerId, int $tenantId): string
    {
        if ($toRaw !== null) {
            return $toRaw;
        }

        if ($customerId !== null) {
            $customer = ApiCustomer::where('id', $customerId)
                ->where('user_id', $tenantId)
                ->withTrashed()
                ->first();

            if ($customer && $customer->phone_number) {
                return (string) $customer->phone_number;
            }
        }

        throw new InvalidPhoneNumberException('No destination phone number provided.');
    }

    private function resolveLine(int $tenantId, ?int $simLineId, int $agentId): CallSimLine
    {
        if ($simLineId !== null) {
            $line = CallSimLine::where('id', $simLineId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();

            if (!$line) {
                throw new NoAvailableLineException('Specified SIM line is not available.');
            }

            return $line;
        }

        // Auto-select: prefer a line pinned to this agent, then any free active line
        $line = CallSimLine::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByRaw('user_id = ? DESC', [$agentId])
            ->first();

        if (!$line) {
            throw new NoAvailableLineException();
        }

        return $line;
    }
}
