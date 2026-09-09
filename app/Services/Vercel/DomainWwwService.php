<?php

namespace App\Services\Vercel;

use App\Models\Api\ApiDomainSetting;
use App\Support\TenantActivity;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DomainWwwService
{
    private const INVENTORY_TTL_SECONDS = 300;

    public function __construct(
        private readonly VercelDomainClient $vercel,
        private readonly VercelDomainCache $domainCache,
        private readonly VercelMutationGuard $mutationGuard,
        private readonly DomainStatusSyncService $domainSyncService,
    ) {
    }

    /**
     * Enable www.<apex> → apex (301). Idempotent when redirect is already correct.
     *
     * @return array{
     *     already_enabled: bool,
     *     hostname: string,
     *     redirect_target: string,
     *     redirect_status_code: int,
     *     domain: ApiDomainSetting
     * }
     *
     * @throws VercelDomainException
     * @throws LockTimeoutException
     */
    public function enable(
        ApiDomainSetting $domain,
        ?Request $request = null,
        bool $requireTypedConfirmation = false
    ): array {
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);

        if ($requireTypedConfirmation) {
            $this->mutationGuard->assertCanMutate($request, $apex);
        } else {
            $this->mutationGuard->assertCanMutate($request);
        }

        try {
            return $this->domainCache->withMutationLock(function () use ($domain, $apex, $request) {
                return $this->enableLocked($domain, $apex, $request);
            });
        } catch (LockTimeoutException $e) {
            throw new VercelDomainException(
                __('domain_mutation.lock_timeout'),
                internalCode: VercelDomainException::CODE_PROVIDER_UNAVAILABLE,
                previous: $e
            );
        }
    }

    /**
     * Stable www status payload derived from persisted last_check (no live Vercel call).
     *
     * @return array{
     *     hostname: string,
     *     present: bool,
     *     redirectCorrect: bool,
     *     dnsMatches: bool,
     *     sslReady: bool,
     *     status: string
     * }
     */
    public function wwwPayload(ApiDomainSetting $domain): array
    {
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);
        $hostname = 'www.' . $apex;
        $dnsRecords = is_array($domain->dns_records) ? $domain->dns_records : [];
        $lastCheck = is_array($dnsRecords['last_check'] ?? null) ? $dnsRecords['last_check'] : null;

        if ($lastCheck === null || $lastCheck === []) {
            return [
                'hostname' => $hostname,
                'present' => false,
                'redirectCorrect' => false,
                'dnsMatches' => false,
                'sslReady' => false,
                'status' => 'unknown',
            ];
        }

        $present = (bool) ($lastCheck['www_present'] ?? false);
        $redirectCorrect = (bool) ($lastCheck['www_redirect_correct'] ?? false);
        $mode = $domain->dns_mode ?: ApiDomainSetting::DNS_MODE_VERCEL_NS;
        $wwwMatches = $lastCheck['www_matches_recommended'] ?? null;

        if ($mode === ApiDomainSetting::DNS_MODE_EXTERNAL_DNS) {
            $dnsMatches = $wwwMatches === true;
        } else {
            $dnsMatches = true;
        }

        $sslReady = (bool) ($lastCheck['www_ssl_ready'] ?? false);

        if (! $present) {
            $status = 'not_enabled';
        } elseif (! $redirectCorrect) {
            $status = 'redirect_misconfigured';
        } elseif ($mode === ApiDomainSetting::DNS_MODE_EXTERNAL_DNS && $wwwMatches !== true) {
            $status = 'dns_pending';
        } elseif (! $sslReady) {
            $status = 'certificate_pending';
        } else {
            $status = 'ready';
        }

        return [
            'hostname' => $hostname,
            'present' => $present,
            'redirectCorrect' => $redirectCorrect,
            'dnsMatches' => $dnsMatches,
            'sslReady' => $sslReady,
            'status' => $status,
        ];
    }

    /**
     * @return array{
     *     already_enabled: bool,
     *     hostname: string,
     *     redirect_target: string,
     *     redirect_status_code: int,
     *     domain: ApiDomainSetting
     * }
     */
    private function enableLocked(ApiDomainSetting $domain, string $apex, ?Request $request): array
    {
        $hostname = 'www.' . $apex;
        $snapshot = $this->domainCache->fresh();

        if ($this->isInventoryUnreliable($snapshot)) {
            throw new VercelDomainException(
                __('vercel_capacity.inventory_unreliable'),
                internalCode: VercelDomainException::CODE_PROVIDER_UNAVAILABLE
            );
        }

        if (! in_array($apex, $snapshot['names'] ?? [], true)) {
            throw new VercelDomainException(
                __('domain_www.apex_not_on_vercel', ['domain' => $apex]),
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        $wwwState = $this->resolveWwwStateForApex($snapshot, $apex);

        if ($wwwState['present']) {
            if (! $wwwState['valid']) {
                throw new VercelDomainException(
                    __('domain_mutation.redirect_mismatch', [
                        'domain' => $hostname,
                        'expected_target' => $apex,
                        'expected_status' => '301',
                    ]),
                    internalCode: VercelDomainException::CODE_REDIRECT_MISMATCH
                );
            }

            $this->refreshDomainState($domain, $request);
            $domain->refresh();

            return [
                'already_enabled' => true,
                'hostname' => $hostname,
                'redirect_target' => $apex,
                'redirect_status_code' => 301,
                'domain' => $domain,
            ];
        }

        $freeEntries = $snapshot['metrics']['free_entries'] ?? null;
        if ($freeEntries !== null && $freeEntries < 1) {
            throw new VercelDomainException(
                __('domain_www.no_free_slot'),
                internalCode: VercelDomainException::CODE_CAPACITY_REACHED
            );
        }

        $before = $this->domainActivitySnapshot($domain);
        $this->vercel->addDomain($hostname, $apex, 301);
        $this->refreshDomainState($domain, $request);
        $domain->refresh();

        if ($request !== null) {
            TenantActivity::emit(
                $request,
                'domain.www_enabled',
                'api_domains_settings',
                $domain->id,
                $before,
                array_merge($this->domainActivitySnapshot($domain), ['www' => $hostname])
            );
        }

        return [
            'already_enabled' => false,
            'hostname' => $hostname,
            'redirect_target' => $apex,
            'redirect_status_code' => 301,
            'domain' => $domain,
        ];
    }

    private function refreshDomainState(ApiDomainSetting $domain, ?Request $request): void
    {
        $this->domainCache->invalidateAdminCaches();
        $projectInventory = $this->domainCache->fresh();

        $this->domainSyncService->sync(
            $domain,
            false,
            $request,
            applyFailureThreshold: false,
            projectInventory: $projectInventory
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array{present: bool, valid: bool}
     */
    private function resolveWwwStateForApex(array $snapshot, string $apex): array
    {
        $www = 'www.' . $apex;

        foreach ($snapshot['domains'] ?? [] as $entry) {
            if ((string) ($entry['name'] ?? '') !== $www) {
                continue;
            }

            $valid = WwwRedirectPolicy::isCorrect($entry, $apex);

            return ['present' => true, 'valid' => $valid];
        }

        return ['present' => false, 'valid' => false];
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     */
    private function isInventoryUnreliable(?array $snapshot): bool
    {
        if ($snapshot === null) {
            return true;
        }

        if (($snapshot['is_lower_bound'] ?? false) === true) {
            return true;
        }

        $fetchedAt = $snapshot['fetched_at'] ?? null;
        if (! is_string($fetchedAt) || $fetchedAt === '') {
            return true;
        }

        return Carbon::parse($fetchedAt)->diffInSeconds(now()) > self::INVENTORY_TTL_SECONDS;
    }

    /**
     * @return array{custom_name: string, status: string, primary: bool, ssl: bool}
     */
    private function domainActivitySnapshot(ApiDomainSetting $domain): array
    {
        return [
            'custom_name' => $this->vercel->normalizeApex((string) $domain->custom_name),
            'status' => (string) $domain->status,
            'primary' => (bool) $domain->primary,
            'ssl' => (bool) $domain->ssl,
        ];
    }
}
