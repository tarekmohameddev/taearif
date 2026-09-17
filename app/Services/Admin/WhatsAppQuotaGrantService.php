<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\WhatsappAddon;
use App\Models\WhatsappAddonAudit;
use App\Services\WhatsApp\WhatsAppQuotaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WhatsAppQuotaGrantService
{
    public function __construct(private readonly WhatsAppQuotaService $quotaService)
    {
    }

    public function grant(array $data, int $adminId, ?string $ipAddress): WhatsappAddon
    {
        return DB::transaction(function () use ($data, $adminId, $ipAddress) {
            $owner = User::query()->lockForUpdate()->findOrFail($data['tenant_id']);
            $reference = 'ADMIN_WA_GRANT_' . $data['idempotency_key'];
            $existing = WhatsappAddon::where('payment_ref', $reference)->first();

            if ($existing) {
                if ((int) $existing->user_id !== (int) $owner->id) {
                    throw ValidationException::withMessages(['idempotency_key' => 'This request key has already been used.']);
                }

                return $existing;
            }

            $before = $this->quotaService->quota($owner);
            $addon = WhatsappAddon::create([
                'user_id' => $owner->id,
                'whatsapp_number_id' => null,
                'plan_id' => null,
                'qty' => $data['quantity'],
                'amount' => 0,
                'status' => WhatsappAddon::STATUS_APPROVED,
                'expire_date' => $data['expires_at'] ?? null,
                'payment_ref' => $reference,
                'gateway_transaction_id' => 'manual_admin_grant',
            ]);
            $after = $this->quotaService->quota($owner);

            $this->audit($addon, $owner->id, $adminId, 'grant', null, $addon->status, $before, $after, $data['reason'], $ipAddress);

            return $addon;
        }, 3);
    }

    public function revoke(WhatsappAddon $addon, string $reason, int $adminId, ?string $ipAddress): WhatsappAddon
    {
        return DB::transaction(function () use ($addon, $reason, $adminId, $ipAddress) {
            $locked = WhatsappAddon::query()->lockForUpdate()->findOrFail($addon->id);
            if (!$locked->user_id || $locked->gateway_transaction_id !== 'manual_admin_grant') {
                throw ValidationException::withMessages(['grant' => 'Only manual tenant quota grants can be revoked here.']);
            }
            if ($locked->status !== WhatsappAddon::STATUS_APPROVED) {
                throw ValidationException::withMessages(['grant' => 'This grant is not active.']);
            }

            $owner = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $before = $this->quotaService->quota($owner);
            $locked->status = WhatsappAddon::STATUS_REJECTED;
            $locked->saveQuietly();
            $after = $this->quotaService->quota($owner);

            $this->audit($locked, $owner->id, $adminId, 'revoke', WhatsappAddon::STATUS_APPROVED, $locked->status, $before, $after, $reason, $ipAddress);

            return $locked;
        }, 3);
    }

    private function audit(WhatsappAddon $addon, int $tenantId, int $adminId, string $action, ?string $oldStatus, string $newStatus, int $oldQuota, int $newQuota, string $reason, ?string $ipAddress): void
    {
        WhatsappAddonAudit::create([
            'tenant_id' => $tenantId,
            'whatsapp_addon_id' => $addon->id,
            'entity_type' => 'addon',
            'action' => $action,
            'quantity' => $addon->qty,
            'old_quota' => $oldQuota,
            'new_quota' => $newQuota,
            'changed_by' => $adminId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $reason,
            'correlation_id' => (string) Str::uuid(),
            'ip_address' => $ipAddress,
            'metadata' => ['payment_ref' => $addon->payment_ref],
            'changed_at' => now(),
        ]);
    }
}
