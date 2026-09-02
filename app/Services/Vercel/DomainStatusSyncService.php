<?php

namespace App\Services\Vercel;

use App\Models\Api\ApiDomainSetting;
use App\Support\TenantActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DomainStatusSyncService
{
    /** @var list<string> */
    private const CONFIRMED_FAILURE_CODES = [
        'not_on_vercel',
        'dns_misconfigured',
        'ns_not_pointing',
        'unverified',
    ];

    public function __construct(
        private readonly VercelDomainClient $vercel,
        private readonly DnsNameserverChecker $nameserverChecker
    ) {
    }

    /**
     * Sync one domain's status/ssl from Vercel + NS + expires_at.
     *
     * @param  array{names: list<string>, domains: list<array<string, mixed>>}|null  $projectInventory
     * @return array{
     *   changed: bool,
     *   old_status: string|null,
     *   new_status: string,
     *   ssl: bool,
     *   message: string,
     *   vercel_verified: bool,
     *   nameservers_ok: bool,
     *   health_code: string
     * }
     */
    public function sync(
        ApiDomainSetting $domain,
        bool $attemptVerify = false,
        ?Request $request = null,
        bool $applyFailureThreshold = true,
        ?array $projectInventory = null
    ): array {
        $oldStatus = (string) $domain->status;
        $oldSsl = (bool) $domain->ssl;
        $apex = $this->vercel->normalizeApex((string) $domain->custom_name);
        $www = 'www.' . $apex;
        $expectedNs = config('services.vercel.nameservers', []);
        $autoAttach = (bool) config('services.vercel.auto_attach_custom_domain', true);
        $checkNameservers = (bool) config('services.vercel.check_nameservers', true);
        $existingDns = is_array($domain->dns_records) ? $domain->dns_records : [];
        $previousCheck = is_array($existingDns['last_check'] ?? null) ? $existingDns['last_check'] : [];

        if ($domain->expires_at && $domain->expires_at->isPast()) {
            return $this->finalize(
                $domain,
                $oldStatus,
                'failed',
                false,
                $this->buildCheckSummary([
                    'health_code' => 'expired',
                    'message' => 'Domain registration has expired.',
                    'reason' => 'expired',
                    'provider_reachable' => true,
                    'consecutive_failures' => (int) ($previousCheck['consecutive_failures'] ?? 0) + 1,
                    'first_failure_at' => $previousCheck['first_failure_at'] ?? now()->toIso8601String(),
                    'auto_attach_custom_domain' => $autoAttach,
                    'nameserver_check_enabled' => $checkNameservers,
                ]),
                $request,
                false,
                false
            );
        }

        if (! $autoAttach && ! $checkNameservers) {
            return $this->finalize(
                $domain,
                $oldStatus,
                $oldStatus,
                $oldSsl,
                $this->buildCheckSummary([
                    'health_code' => 'checks_disabled',
                    'message' => 'Verification checks are disabled (VERCEL_AUTO_ATTACH_CUSTOM_DOMAIN and VERCEL_CHECK_NAMESERVERS are false).',
                    'provider_reachable' => true,
                    'consecutive_failures' => 0,
                    'first_failure_at' => null,
                    'auto_attach_custom_domain' => false,
                    'nameserver_check_enabled' => false,
                ]),
                $request,
                false,
                false
            );
        }

        if ($autoAttach && ! $this->vercel->isConfigured()) {
            $message = 'Vercel domain integration is not configured.';
            Log::warning('DomainStatusSyncService skipped: Vercel not configured', [
                'domain_id' => $domain->id,
            ]);

            return $this->result($oldStatus, $oldStatus, $oldSsl, $message, false, false, 'unchecked');
        }

        $inventory = $projectInventory ?? $this->loadProjectInventory();
        $inventoryDomains = $this->indexInventoryDomains($inventory['domains'] ?? []);
        $apexInventory = $inventoryDomains[$apex] ?? null;
        $wwwInventory = $inventoryDomains[$www] ?? null;
        $apexAttached = $apexInventory !== null;
        $apexVerified = $apexAttached && ! empty($apexInventory['verified']);
        $wwwPresent = $wwwInventory !== null;
        $wwwRedirectCorrect = $this->isWwwRedirectCorrect($wwwInventory, $apex);

        $vercelDomain = $apexInventory;
        $providerError = false;
        $providerReachable = true;
        $message = '';
        $ownershipChallenge = null;
        $domainConfig = [
            'misconfigured' => false,
            'configuredBy' => null,
            'recommendedIPv4' => [],
            'recommendedCNAME' => [],
        ];

        if ($autoAttach) {
            try {
                if ($attemptVerify && $apexAttached) {
                    try {
                        $vercelDomain = $this->vercel->verifyDomain($apex);
                        $apexVerified = ! empty($vercelDomain['verified']);
                        $apexAttached = true;
                    } catch (VercelDomainException $e) {
                        if ($this->isProviderUnknownError($e)) {
                            $providerError = true;
                            $providerReachable = false;
                            $message = 'Could not reach the hosting provider to check this domain.';
                        } else {
                            $vercelDomain = $this->vercel->getDomain($apex);
                            if ($vercelDomain === null) {
                                $apexAttached = false;
                                $apexVerified = false;
                                $message = $message !== '' ? $message : 'Domain is not attached to the Vercel project.';
                            } else {
                                $apexAttached = true;
                                $apexVerified = ! empty($vercelDomain['verified']);
                            }
                        }
                    }
                } elseif (! $apexAttached) {
                    try {
                        $vercelDomain = $this->vercel->getDomain($apex);
                        if ($vercelDomain !== null) {
                            $apexAttached = true;
                            $apexVerified = ! empty($vercelDomain['verified']);
                        } else {
                            $message = 'Domain is not attached to the Vercel project.';
                        }
                    } catch (VercelDomainException $e) {
                        if ($this->isProviderUnknownError($e)) {
                            $providerError = true;
                            $providerReachable = false;
                            $message = 'Could not reach the hosting provider to check this domain.';
                        } else {
                            $message = 'Domain is not attached to the Vercel project.';
                        }
                    }
                }

                if (! $providerError && $apexAttached) {
                    try {
                        $verification = $this->vercel->getDomainVerification($apex);
                        $ownershipChallenge = $this->extractOwnershipChallenge($verification);
                    } catch (VercelDomainException $e) {
                        if ($this->isProviderUnknownError($e)) {
                            $providerError = true;
                            $providerReachable = false;
                            $message = 'Could not reach the hosting provider to check this domain.';
                        }
                    }

                    if (! $providerError) {
                        try {
                            $domainConfig = $this->vercel->getDomainConfig($apex);
                        } catch (VercelDomainException $e) {
                            if ($this->isProviderUnknownError($e)) {
                                $providerError = true;
                                $providerReachable = false;
                                $message = 'Could not reach the hosting provider to check this domain.';
                            } elseif ($e->internalCode === VercelDomainException::CODE_INVALID_DOMAIN) {
                                $apexAttached = false;
                                $apexVerified = false;
                                $message = 'Domain is not attached to the Vercel project.';
                            }
                        }
                    }
                }

                if (! $providerError && $apexAttached && ! $apexVerified && $message === '') {
                    $message = 'Domain is on Vercel but not verified yet. Ensure nameservers have propagated.';
                }
            } catch (VercelDomainException $e) {
                Log::warning('DomainStatusSyncService Vercel error', [
                    'domain_id' => $domain->id,
                    'error' => $e->getMessage(),
                    'vercel_error_code' => $e->getErrorCode(),
                    'internal_code' => $e->internalCode,
                ]);
                $providerError = true;
                $providerReachable = false;
                $message = 'Could not reach the hosting provider to check this domain.';
                $apexVerified = false;
            }
        } else {
            $apexVerified = true;
            $apexAttached = true;
        }

        $observedNameservers = [];
        $nameserversOk = false;

        if ($checkNameservers) {
            try {
                $observedNameservers = $this->nameserverChecker->getObservedNameservers($apex);
                $nameserversOk = $this->nameserverChecker->hasExpectedNameservers($apex, $expectedNs);
                if (! $nameserversOk && $message === '' && ! $providerError) {
                    $message = 'Nameservers are not pointing to Vercel yet.';
                }
            } catch (\Throwable $e) {
                Log::warning('DomainStatusSyncService NS check failed', [
                    'domain_id' => $domain->id,
                    'error' => $e->getMessage(),
                ]);
                $nameserversOk = false;
                $providerError = true;
                $providerReachable = false;
                if ($message === '') {
                    $message = 'Unable to resolve domain nameservers.';
                }
            }
        } else {
            $nameserversOk = true;
        }

        $healthCode = $providerError
            ? 'provider_error'
            : ApiDomainSetting::resolveHealthCode([
                'auto_attach_custom_domain' => $autoAttach,
                'nameserver_check_enabled' => $checkNameservers,
                'apex_attached' => $apexAttached,
                'apex_verified' => $apexVerified,
                'vercel_attached' => $apexAttached,
                'vercel_verified' => $apexVerified,
                'www_present' => $wwwPresent,
                'www_redirect_correct' => $wwwRedirectCorrect,
                'ownership_challenge' => $ownershipChallenge,
                'dns_misconfigured' => (bool) ($domainConfig['misconfigured'] ?? false),
                'nameservers_ok' => $nameserversOk,
                'reason' => null,
            ], $apexAttached);

        if ($message === '') {
            $message = $this->defaultMessageForHealthCode($healthCode);
        }

        [$consecutiveFailures, $firstFailureAt] = $this->resolveFailureCounters(
            $healthCode,
            $previousCheck,
            $providerError
        );

        [$newStatus, $ssl] = $this->resolveStatusTransition(
            $oldStatus,
            $oldSsl,
            $healthCode,
            $autoAttach,
            $applyFailureThreshold,
            $consecutiveFailures,
            $firstFailureAt
        );

        $checkSummary = $this->buildCheckSummary([
            'health_code' => $healthCode,
            'message' => $message,
            'reason' => $providerError ? 'provider_error' : null,
            'provider_reachable' => $providerReachable,
            'apex_attached' => $apexAttached,
            'apex_verified' => $apexVerified,
            'vercel_attached' => $apexAttached,
            'vercel_verified' => $apexVerified,
            'www_present' => $wwwPresent,
            'www_redirect_correct' => $wwwRedirectCorrect,
            'ownership_challenge' => $ownershipChallenge,
            'observed_nameservers' => $observedNameservers,
            'nameservers_ok' => $nameserversOk,
            'nameserver_check_enabled' => $checkNameservers,
            'dns_misconfigured' => (bool) ($domainConfig['misconfigured'] ?? false),
            'configured_by' => $domainConfig['configuredBy'] ?? null,
            'recommended_ipv4' => $domainConfig['recommendedIPv4'] ?? [],
            'recommended_cname' => $domainConfig['recommendedCNAME'] ?? [],
            'consecutive_failures' => $consecutiveFailures,
            'first_failure_at' => $firstFailureAt,
            'auto_attach_custom_domain' => $autoAttach,
        ]);

        return $this->finalize(
            $domain,
            $oldStatus,
            $newStatus,
            $ssl,
            $checkSummary,
            $request,
            $apexVerified,
            $nameserversOk
        );
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    private function buildCheckSummary(array $fields): array
    {
        return array_merge([
            'last_check_at' => now()->toIso8601String(),
        ], $fields);
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     * @return array<string, array<string, mixed>>
     */
    private function indexInventoryDomains(array $domains): array
    {
        $indexed = [];
        foreach ($domains as $domain) {
            if (! is_array($domain)) {
                continue;
            }

            $name = strtolower((string) ($domain['name'] ?? ''));
            if ($name !== '') {
                $indexed[$name] = $domain;
            }
        }

        return $indexed;
    }

    /**
     * @return array{names: list<string>, domains: list<array<string, mixed>>}
     */
    private function loadProjectInventory(): array
    {
        try {
            return $this->vercel->listProjectDomains();
        } catch (VercelDomainException $e) {
            Log::warning('DomainStatusSyncService inventory fetch failed', [
                'error' => $e->getMessage(),
            ]);

            return ['names' => [], 'domains' => []];
        }
    }

    /**
     * @param  array<string, mixed>|null  $wwwInventory
     */
    private function isWwwRedirectCorrect(?array $wwwInventory, string $apex): bool
    {
        if ($wwwInventory === null) {
            return false;
        }

        $redirect = isset($wwwInventory['redirect']) ? strtolower((string) $wwwInventory['redirect']) : null;
        $statusCode = isset($wwwInventory['redirectStatusCode']) ? (int) $wwwInventory['redirectStatusCode'] : null;

        if ($redirect !== $apex) {
            return false;
        }

        return $statusCode === null || in_array($statusCode, [301, 308], true);
    }

    /**
     * @param  list<array<string, mixed>>  $verification
     * @return array{type: string, domain: string, value: string, reason?: string}|null
     */
    private function extractOwnershipChallenge(array $verification): ?array
    {
        foreach ($verification as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = strtolower((string) ($item['type'] ?? ''));
            if ($type !== 'txt') {
                continue;
            }

            $domain = (string) ($item['domain'] ?? '');
            $value = (string) ($item['value'] ?? '');
            if ($domain === '' || $value === '') {
                continue;
            }

            return array_filter([
                'type' => 'txt',
                'domain' => strtolower($domain),
                'value' => $value,
                'reason' => $item['reason'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');
        }

        return null;
    }

    private function isProviderUnknownError(VercelDomainException $e): bool
    {
        $internal = $e->internalCode;

        return in_array($internal, [
            VercelDomainException::CODE_RATE_LIMITED,
            VercelDomainException::CODE_PROVIDER_UNAVAILABLE,
        ], true) || in_array($e->statusCode, [408, 429, 500, 502, 503, 504], true);
    }

    /**
     * @param  array<string, mixed>  $previousCheck
     * @return array{0: int, 1: string|null}
     */
    private function resolveFailureCounters(string $healthCode, array $previousCheck, bool $providerError): array
    {
        if ($providerError) {
            return [
                (int) ($previousCheck['consecutive_failures'] ?? 0),
                isset($previousCheck['first_failure_at']) ? (string) $previousCheck['first_failure_at'] : null,
            ];
        }

        if (in_array($healthCode, ['linked', 'apex_only', 'checks_disabled'], true)) {
            return [0, null];
        }

        if ($healthCode === 'expired') {
            $firstFailureAt = isset($previousCheck['first_failure_at'])
                ? (string) $previousCheck['first_failure_at']
                : now()->toIso8601String();

            return [(int) ($previousCheck['consecutive_failures'] ?? 0) + 1, $firstFailureAt];
        }

        if (! in_array($healthCode, self::CONFIRMED_FAILURE_CODES, true)) {
            return [0, null];
        }

        $previousCount = (int) ($previousCheck['consecutive_failures'] ?? 0);
        $firstFailureAt = isset($previousCheck['first_failure_at'])
            ? (string) $previousCheck['first_failure_at']
            : now()->toIso8601String();

        return [$previousCount + 1, $firstFailureAt];
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private function resolveStatusTransition(
        string $oldStatus,
        bool $oldSsl,
        string $healthCode,
        bool $autoAttach,
        bool $applyFailureThreshold,
        int $consecutiveFailures,
        ?string $firstFailureAt
    ): array {
        if ($healthCode === 'provider_error') {
            return [$oldStatus, $oldSsl];
        }

        if (in_array($healthCode, ['linked', 'apex_only'], true)) {
            return [
                'active',
                $autoAttach,
            ];
        }

        if ($healthCode === 'expired') {
            return ['failed', false];
        }

        if ($oldStatus === 'active' && in_array($healthCode, self::CONFIRMED_FAILURE_CODES, true)) {
            if (! $applyFailureThreshold || $this->thresholdMet($consecutiveFailures, $firstFailureAt)) {
                return ['failed', false];
            }

            return [$oldStatus, $oldSsl];
        }

        if ($oldStatus === 'active') {
            return [$oldStatus, $oldSsl];
        }

        if ($oldStatus === 'failed' && in_array($healthCode, self::CONFIRMED_FAILURE_CODES, true)) {
            return ['failed', false];
        }

        return ['pending', false];
    }

    private function thresholdMet(int $consecutiveFailures, ?string $firstFailureAt): bool
    {
        $threshold = max(1, (int) config('services.vercel.health_failure_threshold', 3));
        if ($consecutiveFailures < $threshold) {
            return false;
        }

        $graceHours = max(0, (int) config('services.vercel.health_failure_grace_hours', 0));
        if ($graceHours === 0 || $firstFailureAt === null) {
            return true;
        }

        return now()->diffInHours($firstFailureAt) >= $graceHours;
    }

    private function defaultMessageForHealthCode(string $healthCode): string
    {
        return match ($healthCode) {
            'linked' => 'Domain is verified, DNS is configured, and nameservers are correct.',
            'apex_only' => 'Apex domain is linked; optional www redirect is not configured.',
            'ownership_required' => 'Add the ownership TXT record at your DNS provider to verify this domain.',
            'dns_misconfigured' => 'DNS records are misconfigured according to the hosting provider.',
            'ns_not_pointing' => 'Nameservers are not pointing to Vercel yet.',
            'not_on_vercel' => 'Domain is not attached to the Vercel project.',
            'unverified' => 'Domain is on Vercel but not verified yet. Ensure nameservers have propagated.',
            'expired' => 'Domain registration has expired.',
            'provider_error' => 'Could not reach the hosting provider to check this domain.',
            'checks_disabled' => 'Verification checks are disabled (VERCEL_AUTO_ATTACH_CUSTOM_DOMAIN and VERCEL_CHECK_NAMESERVERS are false).',
            default => 'Domain verification is still pending.',
        };
    }

    /**
     * @param  array<string, mixed>  $checkSummary
     * @return array{
     *   changed: bool,
     *   old_status: string|null,
     *   new_status: string,
     *   ssl: bool,
     *   message: string,
     *   vercel_verified: bool,
     *   nameservers_ok: bool,
     *   health_code: string
     * }
     */
    private function finalize(
        ApiDomainSetting $domain,
        ?string $oldStatus,
        string $newStatus,
        bool $ssl,
        array $checkSummary,
        ?Request $request,
        bool $vercelVerified,
        bool $nameserversOk
    ): array {
        $this->persist($domain, $newStatus, $ssl, $checkSummary, $oldStatus, $request);

        return $this->result(
            $oldStatus,
            $newStatus,
            $ssl,
            (string) ($checkSummary['message'] ?? ''),
            $vercelVerified,
            $nameserversOk,
            (string) ($checkSummary['health_code'] ?? 'unchecked')
        );
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
     *   nameservers_ok: bool,
     *   health_code: string
     * }
     */
    private function result(
        ?string $oldStatus,
        string $newStatus,
        bool $ssl,
        string $message,
        bool $vercelVerified,
        bool $nameserversOk,
        string $healthCode
    ): array {
        return [
            'changed' => $oldStatus !== $newStatus,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'ssl' => $ssl,
            'message' => $message,
            'vercel_verified' => $vercelVerified,
            'nameservers_ok' => $nameserversOk,
            'health_code' => $healthCode,
        ];
    }
}
