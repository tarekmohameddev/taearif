<?php

declare(strict_types=1);

namespace App\Domain\Calling\Services;

use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Models\CallTrunk;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tenant-scoped PSTN loopback for environments without a Yeastar device.
 *
 * When enabled, AMI originate still rings the CRM agent's WebRTC first,
 * then the PBX second leg dials a local SIP endpoint (default: agent_1002)
 * instead of a GSM trunk.
 */
final class CallingLoopbackService
{
    public function isEnabledForTenant(int $tenantId): bool
    {
        /** @var list<int> $ids */
        $ids = config('calling.loopback.tenant_ids', []);

        return in_array($tenantId, $ids, true);
    }

    public function destEndpoint(): string
    {
        return (string) config('calling.loopback.dest_endpoint', 'agent_1002');
    }

    public function trunkSentinel(): string
    {
        return (string) config('calling.loopback.trunk_sentinel', 'loopback');
    }

    /**
     * Ensure a dummy registered SIM line exists so place-call and GET /sim-lines
     * work without a real Yeastar trunk.
     *
     * Serialized per tenant: call_trunks has no unique key on
     * (tenant_id, asterisk_endpoint_prefix), so two concurrent firstOrCreate
     * calls would insert duplicate trunks and then collide on
     * call_sim_lines.asterisk_endpoint.
     */
    public function ensureDummyLine(int $tenantId): CallSimLine
    {
        return Cache::lock("calling:loopback:dummy:{$tenantId}", 15)
            ->block(15, function () use ($tenantId): CallSimLine {
                return DB::transaction(function () use ($tenantId): CallSimLine {
                    return $this->findOrCreateDummyLine($tenantId);
                });
            });
    }

    private function findOrCreateDummyLine(int $tenantId): CallSimLine
    {
        $endpoint = $this->dummyEndpointId($tenantId);

        $existing = CallSimLine::where('tenant_id', $tenantId)
            ->where('asterisk_endpoint', $endpoint)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            return $this->activateDummyLine($existing);
        }

        $trunk = CallTrunk::query()
            ->where('tenant_id', $tenantId)
            ->where('asterisk_endpoint_prefix', $endpoint)
            ->lockForUpdate()
            ->first();

        if ($trunk === null) {
            $trunk = CallTrunk::create([
                'tenant_id'                => $tenantId,
                'asterisk_endpoint_prefix' => $endpoint,
                'name'                     => 'Loopback (test)',
                'type'                     => 'yeastar_gsm',
                'ownership'                => 'company_hosted',
                'registration_mode'        => 'ip_auth',
                'status'                   => 'registered',
                'meta'                     => ['loopback' => true],
            ]);
        } elseif ($trunk->status !== 'registered') {
            $trunk->status = 'registered';
            $trunk->save();
        }

        try {
            return CallSimLine::create([
                'tenant_id'         => $tenantId,
                'trunk_id'          => $trunk->id,
                'label'             => 'Loopback test',
                'msisdn'            => (string) config('calling.loopback.from_e164', '+966500000002'),
                'asterisk_endpoint' => $endpoint,
                'port_index'        => 1,
                'is_active'         => true,
            ]);
        } catch (QueryException $e) {
            if (!$this->isUniqueConstraintViolation($e)) {
                throw $e;
            }

            $line = CallSimLine::where('asterisk_endpoint', $endpoint)
                ->where('tenant_id', $tenantId)
                ->first();

            if ($line === null) {
                throw $e;
            }

            return $this->activateDummyLine($line);
        }
    }

    private function activateDummyLine(CallSimLine $line): CallSimLine
    {
        if (!$line->is_active) {
            $line->is_active = true;
            $line->save();
        }

        $line->loadMissing('trunk');
        if ($line->trunk && $line->trunk->status !== 'registered') {
            $line->trunk->status = 'registered';
            $line->trunk->save();
        }

        return $line;
    }

    private function dummyEndpointId(int $tenantId): string
    {
        return $this->trunkSentinel() . '_' . $tenantId;
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        return ($errorInfo[0] ?? null) === '23000'
            && (int) ($errorInfo[1] ?? 0) === 1062;
    }
}
