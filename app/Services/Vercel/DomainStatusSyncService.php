<?php

namespace App\Services\Vercel;

use App\Models\Api\ApiDomainSetting;
use App\Support\TenantActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomainStatusSyncService
{
    public function __construct(
        private readonly VercelDomainClient $vercel,
        private readonly DnsNameserverChecker $nameserverChecker
    ) {
    }

    /**
     * Sync one domain's status/ssl from Vercel + NS + expires_at.
     *
     * @return array{
     *   changed: bool,
     *   old_status: string|null,
     *   new_status: string,
     *   ssl: bool,
     *   message: string,
     *   vercel_verified: bool,
     *   nameservers_ok: bool
     * }
     */
    public function sync(ApiDomainSetting $domain, bool $attemptVerify = false, ?Request $request = null): array
    {
        $oldStatus = $domain->status;
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);
        $expectedNs = config('services.vercel.nameservers', []);

        $vercelVerified = false;
        $nameserversOk = false;
        $message = '';

        if ($domain->expires_at && $domain->expires_at->isPast()) {
            $newStatus = 'failed';
            $ssl = false;
            $message = 'Domain registration has expired.';
            $this->persist($domain, $newStatus, $ssl, [
                'last_check_at' => now()->toIso8601String(),
                'vercel_verified' => false,
                'nameservers_ok' => false,
                'message' => $message,
                'reason' => 'expired',
            ], $oldStatus, $request);

            return $this->result($oldStatus, $newStatus, $ssl, $message, false, false);
        }

        if (! $this->vercel->isConfigured()) {
            $message = 'Vercel domain integration is not configured.';
            Log::warning('DomainStatusSyncService skipped: Vercel not configured', [
                'domain_id' => $domain->id,
            ]);

            return $this->result($oldStatus, (string) $oldStatus, (bool) $domain->ssl, $message, false, false);
        }

        try {
            $vercelDomain = null;
            if ($attemptVerify) {
                try {
                    $vercelDomain = $this->vercel->verifyDomain($apex);
                } catch (VercelDomainException $e) {
                    $vercelDomain = $this->vercel->getDomain($apex);
                    if ($vercelDomain === null) {
                        $message = $e->getMessage();
                    }
                }
            } else {
                $vercelDomain = $this->vercel->getDomain($apex);
            }

            if ($vercelDomain === null) {
                $message = $message !== '' ? $message : 'Domain is not attached to the Vercel project.';
            } else {
                $vercelVerified = ! empty($vercelDomain['verified']);
                if (! $vercelVerified) {
                    $message = 'Domain is on Vercel but not verified yet. Ensure nameservers have propagated.';
                }
            }
        } catch (VercelDomainException $e) {
            Log::warning('DomainStatusSyncService Vercel error', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);
            $message = $e->getMessage();
            $vercelVerified = false;
        }

        try {
            $nameserversOk = $this->nameserverChecker->hasExpectedNameservers($apex, $expectedNs);
            if (! $nameserversOk && $message === '') {
                $message = 'Nameservers are not pointing to Vercel yet.';
            }
        } catch (\Throwable $e) {
            Log::warning('DomainStatusSyncService NS check failed', [
                'domain_id' => $domain->id,
                'error' => $e->getMessage(),
            ]);
            $nameserversOk = false;
            if ($message === '') {
                $message = 'Unable to resolve domain nameservers.';
            }
        }

        if ($vercelVerified && $nameserversOk) {
            $newStatus = 'active';
            $ssl = true;
            $message = 'Domain is verified and nameservers are correct.';
        } elseif ($oldStatus === 'active') {
            $newStatus = 'failed';
            $ssl = false;
            if ($message === '') {
                $message = 'Domain is no longer verified or nameservers changed.';
            }
        } else {
            $newStatus = 'pending';
            $ssl = false;
            if ($message === '') {
                $message = 'Domain verification is still pending.';
            }
        }

        $this->persist($domain, $newStatus, $ssl, [
            'last_check_at' => now()->toIso8601String(),
            'vercel_verified' => $vercelVerified,
            'nameservers_ok' => $nameserversOk,
            'message' => $message,
        ], $oldStatus, $request);

        return $this->result($oldStatus, $newStatus, $ssl, $message, $vercelVerified, $nameserversOk);
    }

    /**
     * @param  array<string, mixed>  $checkSummary
     */
    private function persist(
        ApiDomainSetting $domain,
        string $newStatus,
        bool $ssl,
        array $checkSummary,
        ?string $oldStatus,
        ?Request $request
    ): void {
        $changed = $domain->status !== $newStatus || (bool) $domain->ssl !== $ssl;

        $existingDns = is_array($domain->dns_records) ? $domain->dns_records : [];
        $domain->dns_records = array_merge($existingDns, ['last_check' => $checkSummary]);
        $domain->status = $newStatus;
        $domain->ssl = $ssl;
        $domain->save();

        if ($changed && $request) {
            TenantActivity::emit(
                $request,
                'domain.status_synced',
                'api_domains_settings',
                $domain->id,
                ['old_status' => $oldStatus],
                ['new_status' => $newStatus, 'ssl' => $ssl]
            );
        }
    }

    /**
     * @return array{
     *   changed: bool,
     *   old_status: string|null,
     *   new_status: string,
     *   ssl: bool,
     *   message: string,
     *   vercel_verified: bool,
     *   nameservers_ok: bool
     * }
     */
    private function result(
        ?string $oldStatus,
        string $newStatus,
        bool $ssl,
        string $message,
        bool $vercelVerified,
        bool $nameserversOk
    ): array {
        return [
            'changed' => $oldStatus !== $newStatus,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'ssl' => $ssl,
            'message' => $message,
            'vercel_verified' => $vercelVerified,
            'nameservers_ok' => $nameserversOk,
        ];
    }
}
