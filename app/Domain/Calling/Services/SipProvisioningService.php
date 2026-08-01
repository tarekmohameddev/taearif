<?php

declare(strict_types=1);

namespace App\Domain\Calling\Services;

use App\Domain\Calling\Exceptions\CallingModuleDisabledException;
use App\Domain\Calling\Models\CallAgentExtension;
use App\Domain\Calling\Models\CallSetting;
use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Repositories\AsteriskRealtimeRepository;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SipProvisioningService
{
    public function __construct(
        private readonly AsteriskRealtimeRepository $asterisk
    ) {}

    // -----------------------------------------------------------------
    // Agent extensions
    // -----------------------------------------------------------------

    /**
     * Enable calling for a CRM user (tenant owner or employee).
     * Creates the CRM record + three ps_* rows on Asterisk.
     *
     * @throws CallingModuleDisabledException if calling is not enabled for the tenant
     */
    public function provisionAgent(User $user): CallAgentExtension
    {
        $tenantId = $user->tenantOwnerId();

        $settings = CallSetting::where('tenant_id', $tenantId)->first();
        if (!$settings || !$settings->enabled) {
            throw new CallingModuleDisabledException();
        }

        $sipUsername = "agent_{$tenantId}_{$user->id}";
        $sipPassword = Str::random(32);
        $context     = config('calling.contexts.outbound');

        // Resolve a sensible caller ID name and extension
        $callerIdName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            ?: ($user->company_name ?? $user->username ?? $sipUsername);
        $extension    = (string) $user->id;

        return DB::transaction(function () use (
            $user, $tenantId, $sipUsername, $sipPassword, $context, $callerIdName, $extension
        ) {
            $row = CallAgentExtension::updateOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => $user->id],
                [
                    'sip_username'         => $sipUsername,
                    'sip_password_encrypted' => Crypt::encryptString($sipPassword),
                    'extension'            => $extension,
                    'asterisk_context'     => $context,
                    'is_active'            => true,
                ]
            );

            $this->asterisk->upsertAgent(
                $sipUsername,
                $sipPassword,
                $context,
                $callerIdName,
                $extension
            );

            return $row;
        });
    }

    /**
     * Disable calling for a CRM user. Removes ps_* rows and marks CRM row inactive.
     */
    public function deprovisionAgent(User $user): void
    {
        $tenantId    = $user->tenantOwnerId();
        $sipUsername = "agent_{$tenantId}_{$user->id}";

        DB::transaction(function () use ($user, $tenantId, $sipUsername) {
            CallAgentExtension::where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->update(['is_active' => false]);

            $this->asterisk->deleteAgent($sipUsername);
        });
    }

    /**
     * Return the decrypted SIP password for the given extension (used by softphone-config endpoint).
     */
    public function decryptPassword(CallAgentExtension $ext): string
    {
        return Crypt::decryptString($ext->sip_password_encrypted);
    }

    // -----------------------------------------------------------------
    // Trunk endpoints
    // -----------------------------------------------------------------

    /**
     * Provision a Yeastar GSM port on Asterisk and create the CallSimLine record.
     */
    public function provisionYeastarPort(CallTrunk $trunk, int $portIndex, string $msisdn, ?int $userId = null): CallSimLine
    {
        $endpointId = "{$trunk->asterisk_endpoint_prefix}_gsm{$portIndex}";
        $password   = Str::random(32);
        $context    = config('calling.contexts.inbound');

        return DB::transaction(function () use ($trunk, $portIndex, $msisdn, $userId, $endpointId, $password, $context) {
            $line = CallSimLine::updateOrCreate(
                ['trunk_id' => $trunk->id, 'port_index' => $portIndex],
                [
                    'tenant_id'          => $trunk->tenant_id,
                    'label'              => "GSM Port {$portIndex}",
                    'msisdn'             => $msisdn,
                    'asterisk_endpoint'  => $endpointId,
                    'user_id'            => $userId,
                    'is_active'          => true,
                ]
            );

            $this->asterisk->upsertTrunk($endpointId, $password, $context);

            // Merge the new port password with any previously stored port credentials.
            // Overwriting would lose passwords for already-provisioned ports on the same trunk.
            $existing = [];
            if ($trunk->credentials_encrypted) {
                try {
                    $existing = json_decode(Crypt::decryptString($trunk->credentials_encrypted), true) ?? [];
                } catch (\Throwable) {
                    $existing = [];
                }
            }
            $existing["port_{$portIndex}_password"] = $password;
            $trunk->credentials_encrypted = Crypt::encryptString(json_encode($existing));
            $trunk->save();

            return $line;
        });
    }

    /**
     * Provision an STC SIP trunk endpoint (IP auth — no device registration required).
     */
    public function provisionStcTrunk(CallTrunk $trunk, string $msisdn, string $stcHost): CallSimLine
    {
        $endpointId = "{$trunk->asterisk_endpoint_prefix}_stc";
        $context    = config('calling.contexts.inbound');

        return DB::transaction(function () use ($trunk, $msisdn, $endpointId, $context, $stcHost) {
            $line = CallSimLine::updateOrCreate(
                ['trunk_id' => $trunk->id, 'port_index' => null],
                [
                    'tenant_id'         => $trunk->tenant_id,
                    'label'             => 'STC SIP',
                    'msisdn'            => $msisdn,
                    'asterisk_endpoint' => $endpointId,
                    'is_active'         => true,
                ]
            );

            // STC uses IP auth; transport-udp only, no webrtc
            $this->asterisk->upsertTrunk($endpointId, Str::random(32), $context, 'transport-udp');

            $trunk->meta = array_merge($trunk->meta ?? [], ['stc_host' => $stcHost]);
            $trunk->save();

            return $line;
        });
    }

    /**
     * Remove a trunk's Asterisk endpoints and deactivate its sim lines.
     */
    public function deprovisionTrunk(CallTrunk $trunk): void
    {
        DB::transaction(function () use ($trunk) {
            foreach ($trunk->simLines as $line) {
                $this->asterisk->deleteTrunk($line->asterisk_endpoint);
            }
            $trunk->simLines()->update(['is_active' => false]);
            $trunk->delete();
        });
    }
}
