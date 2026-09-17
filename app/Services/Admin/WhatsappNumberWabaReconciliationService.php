<?php

namespace App\Services\Admin;

use App\Domain\Communication\WhatsApp\Services\SyncWhatsappUserToWaNumberService;
use App\Models\WhatsappUser;
use App\Models\WhatsappWabaReconciliationLog;
use App\Services\MetaGraphService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhatsappNumberWabaReconciliationService
{
    public function __construct(
        private WhatsappNumberDiagnosticsService $diagnostics,
        private SyncWhatsappUserToWaNumberService $syncWaNumber,
        private MetaGraphService $metaGraph
    ) {
    }

    /**
     * @param  array<string,string|null>  $expected
     * @return array<string,mixed>
     */
    public function reconcile(
        int $whatsappUserId,
        array $expected,
        int $adminId
    ): array {
        $snapshot = WhatsappUser::query()->find($whatsappUserId);
        if ($snapshot === null) {
            return $this->blockedResult([], 'not_found');
        }

        $accessToken = trim((string) ($snapshot->access_token ?? ''));
        $tokenFingerprint = hash('sha256', $accessToken);
        $snapshotPhoneId = trim((string) ($snapshot->phone_id ?? ''));
        $oldWabaId = trim((string) ($snapshot->waba_id ?? ''));
        $snapshotUpdatedAt = (string) ($snapshot->updated_at ?? '');

        $diagnostics = $this->diagnostics->diagnose($whatsappUserId);
        $reconciliation = $diagnostics['reconciliation'] ?? [];

        $afterDiagnostics = WhatsappUser::query()->find($whatsappUserId);
        if ($afterDiagnostics === null || $this->connectionChanged(
            $afterDiagnostics,
            $tokenFingerprint,
            $snapshotPhoneId,
            $oldWabaId,
            $snapshotUpdatedAt
        )) {
            return $this->blockedResult($diagnostics, 'stale_state');
        }

        if (($reconciliation['state'] ?? '') !== 'eligible') {
            return $this->blockedResult(
                $diagnostics,
                (string) ($reconciliation['reason_code'] ?? 'not_eligible')
            );
        }

        if (! $this->expectedStateMatches($reconciliation, $expected)) {
            return $this->blockedResult($diagnostics, 'stale_confirmation');
        }

        $verifiedWabaId = (string) $reconciliation['verified_waba_id'];
        $phoneId = (string) $reconciliation['phone_id'];

        if ($snapshotPhoneId !== $phoneId || $oldWabaId !== (string) $reconciliation['stored_waba_id']) {
            return $this->blockedResult($diagnostics, 'stale_state');
        }

        $transactionResult = DB::transaction(function () use (
            $whatsappUserId,
            $adminId,
            $accessToken,
            $tokenFingerprint,
            $verifiedWabaId,
            $phoneId,
            $oldWabaId,
            $snapshotUpdatedAt
        ) {
            $number = WhatsappUser::query()->whereKey($whatsappUserId)->lockForUpdate()->first();

            if ($number === null) {
                return ['blocked' => true, 'reason_code' => 'not_found'];
            }

            $currentToken = trim((string) ($number->access_token ?? ''));
            $stateChanged = $this->connectionChanged(
                $number,
                $tokenFingerprint,
                $phoneId,
                $oldWabaId,
                $snapshotUpdatedAt
            );

            if ($stateChanged || ! hash_equals($accessToken, $currentToken)) {
                return ['blocked' => true, 'reason_code' => 'stale_state'];
            }

            $ownerMismatch = DB::table('wa_numbers')
                ->where('provider', 'meta')
                ->where('phone_number_id', $phoneId)
                ->lockForUpdate()
                ->get(['user_id'])
                ->contains(fn ($waNumber) => (int) $waNumber->user_id !== (int) $number->user_id);

            if ($ownerMismatch) {
                return ['blocked' => true, 'reason_code' => 'owner_mismatch'];
            }

            $number->waba_id = $verifiedWabaId;
            $number->business_id = $verifiedWabaId;
            $number->save();

            $waNumber = $this->syncWaNumber->sync($number, 'meta');
            if ($waNumber === null) {
                throw new \RuntimeException('The Communication WhatsApp number could not be synchronized.');
            }

            $audit = WhatsappWabaReconciliationLog::query()->create([
                'whatsapp_user_id' => $number->id,
                'tenant_owner_id' => $number->user_id,
                'admin_id' => $adminId,
                'phone_id' => $phoneId,
                'old_waba_id' => $oldWabaId !== '' ? $oldWabaId : null,
                'verified_waba_id' => $verifiedWabaId,
                'result' => 'local_repaired',
                'reason_code' => 'unique_phone_match',
                'subscription_result' => 'pending',
            ]);

            return [
                'blocked' => false,
                'audit_id' => $audit->id,
                'tenant_owner_id' => (int) $number->user_id,
            ];
        }, 3);

        if (($transactionResult['blocked'] ?? false) === true) {
            return $this->blockedResult(
                $this->diagnostics->diagnose($whatsappUserId),
                (string) ($transactionResult['reason_code'] ?? 'stale_state')
            );
        }

        $subscriptionResult = 'subscribed';
        $result = 'success';

        try {
            $subscription = $this->metaGraph->subscribeAppToWaba($accessToken, $verifiedWabaId, [
                'source' => 'admin_waba_reconciliation',
                'whatsapp_user_id' => $whatsappUserId,
                'tenant_owner_id' => $transactionResult['tenant_owner_id'],
                'admin_id' => $adminId,
            ]);

            if (($subscription['success'] ?? false) !== true) {
                throw new \RuntimeException('Meta did not confirm the WABA subscription.');
            }
        } catch (\Throwable $e) {
            $subscriptionResult = 'failed';
            $result = 'subscription_warning';

            Log::warning('admin.whatsapp_waba_reconciliation.subscription_failed', [
                'whatsapp_user_id' => $whatsappUserId,
                'tenant_owner_id' => $transactionResult['tenant_owner_id'],
                'admin_id' => $adminId,
                'verified_waba_id' => $verifiedWabaId,
                'exception' => get_class($e),
            ]);
        }

        try {
            WhatsappWabaReconciliationLog::query()
                ->whereKey($transactionResult['audit_id'])
                ->update([
                    'result' => $result,
                    'subscription_result' => $subscriptionResult,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::error('admin.whatsapp_waba_reconciliation.audit_finalize_failed', [
                'whatsapp_user_id' => $whatsappUserId,
                'audit_id' => $transactionResult['audit_id'],
                'exception' => get_class($e),
            ]);
        }

        return [
            'ok' => true,
            'result' => $result,
            'reason_code' => 'unique_phone_match',
            'diagnostics' => $this->diagnostics->diagnose($whatsappUserId),
        ];
    }

    /**
     * @param  array<string,mixed>  $reconciliation
     * @param  array<string,string|null>  $expected
     */
    private function expectedStateMatches(array $reconciliation, array $expected): bool
    {
        return (string) ($reconciliation['phone_id'] ?? '') === (string) ($expected['phone_id'] ?? '')
            && (string) ($reconciliation['stored_waba_id'] ?? '') === (string) ($expected['stored_waba_id'] ?? '')
            && (string) ($reconciliation['verified_waba_id'] ?? '') === (string) ($expected['verified_waba_id'] ?? '');
    }

    private function connectionChanged(
        WhatsappUser $number,
        string $tokenFingerprint,
        string $phoneId,
        string $wabaId,
        string $updatedAt
    ): bool {
        return ! hash_equals(
            $tokenFingerprint,
            hash('sha256', trim((string) ($number->access_token ?? '')))
        )
            || trim((string) ($number->phone_id ?? '')) !== $phoneId
            || trim((string) ($number->waba_id ?? '')) !== $wabaId
            || (string) ($number->updated_at ?? '') !== $updatedAt;
    }

    /**
     * @param  array<string,mixed>  $diagnostics
     * @return array<string,mixed>
     */
    private function blockedResult(array $diagnostics, string $reasonCode): array
    {
        return [
            'ok' => false,
            'result' => 'blocked',
            'reason_code' => $reasonCode,
            'diagnostics' => $diagnostics,
        ];
    }
}
