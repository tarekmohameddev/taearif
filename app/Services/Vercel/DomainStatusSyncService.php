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
        $autoAttach = (bool) config('services.vercel.auto_attach_custom_domain', true);
        $checkNameservers = (bool) config('services.vercel.check_nameservers', true);

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
                'auto_attach_custom_domain' => $autoAttach,
                'nameserver_check_enabled' => $checkNameservers,
                'message' => $message,
                'reason' => 'expired',
            ], $oldStatus, $request);

            return $this->result($oldStatus, $newStatus, $ssl, $message, false, false);
        }

        if (! $autoAttach && ! $checkNameservers) {
            $message = 'Verification checks are disabled (VERCEL_AUTO_ATTACH_CUSTOM_DOMAIN and VERCEL_CHECK_NAMESERVERS are false).';
            $this->persist($domain, (string) $oldStatus, (bool) $domain->ssl, [
                'last_check_at' => now()->toIso8601String(),
                'vercel_verified' => false,
                'nameservers_ok' => false,
                'auto_attach_custom_domain' => false,
                'nameserver_check_enabled' => false,
                'message' => $message,
            ], $oldStatus, $request);

            return $this->result($oldStatus, (string) $oldStatus, (bool) $domain->ssl, $message, false, false);
        }

        if ($autoAttach) {
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
                            // $message reaches the client. Never derive it from
                            // getMessage(), which embeds the raw upstream text.
                            Log::warning('DomainStatusSyncService verify failed', [
                                'domain_id' => $domain->id,
                                'error' => $e->getMessage(),
                                'vercel_error_code' => $e->getErrorCode(),
                            ]);
                            $message = 'Domain could not be verified with the hosting provider yet.';
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
                    'vercel_error_code' => $e->getErrorCode(),
                ]);
                // Client-facing: the raw upstream message names the Vercel project
                // and, with no error.message field, serialises the whole body.
                $message = 'Could not reach the hosting provider to check this domain.';
                $vercelVerified = false;
            }
        } else {
            // Not attaching to Vercel — do not block activation on Vercel status.
            $vercelVerified = true;
        }

        if ($checkNameservers) {
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
        } else {
            $nameserversOk = true;
        }

        if ($vercelVerified && $nameserversOk) {
            $newStatus = 'active';
            $ssl = $autoAttach ? true : (bool) $domain->ssl;
            $message = $autoAttach && $checkNameservers
                ? 'Domain is verified and nameservers are correct.'
                : ($autoAttach
                    ? 'Domain is verified on Vercel.'
                    : 'Nameservers are correct.');
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
            'auto_attach_custom_domain' => $autoAttach,
            'nameserver_check_enabled' => $checkNameservers,
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
