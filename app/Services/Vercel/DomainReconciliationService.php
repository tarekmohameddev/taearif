<?php

namespace App\Services\Vercel;

use App\Models\Api\ApiDomainSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DomainReconciliationService
{
    public function __construct(
        private readonly VercelDomainClient $client,
        private readonly VercelDomainInventoryService $inventory,
        private readonly VercelDomainCache $cache,
        private readonly VercelMutationGuard $mutationGuard
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReport(bool $fetchFresh = true): array
    {
        $snapshot = $fetchFresh
            ? $this->inventory->buildSnapshot(fetchFresh: true)
            : ($this->cache->cached() ?? $this->inventory->buildSnapshot(fetchFresh: true));

        return $this->buildReportFromSnapshot($snapshot);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>
     */
    public function buildReportFromSnapshot(array $snapshot): array
    {
        $domains = is_array($snapshot['domains'] ?? null) ? $snapshot['domains'] : [];
        $names = is_array($snapshot['names'] ?? null) ? $snapshot['names'] : [];

        $platformSet = $this->platformDomainSet();
        $vercelSet = array_fill_keys($names, true);

        $dbRows = ApiDomainSetting::query()
            ->select(['id', 'custom_name', 'status', 'dns_records', 'custom_domain_id'])
            ->orderBy('id')
            ->get();

        $dbApexMap = [];
        foreach ($dbRows as $row) {
            $apex = $this->client->normalizeApex((string) $row->custom_name);
            if ($apex === '' || $this->isWildcard($apex)) {
                continue;
            }
            $dbApexMap[$apex][] = $row;
        }

        $legacyApexMap = $this->buildLegacyApexMap();

        $indexed = $this->indexDomainsByApex($domains, $platformSet);

        $protectedPlatform = [];
        $vercelOnlyOrphan = [];
        $apexWithOptionalWww = [];
        $wwwWithoutApex = [];
        $apexWithoutWww = [];
        $incorrectRedirect = [];

        foreach ($indexed as $apex => $group) {
            if ($group['wildcard']) {
                continue;
            }

            if ($group['platform']) {
                $protectedPlatform[] = [
                    'apex' => $apex,
                    'vercel_names' => $group['names'],
                ];
                continue;
            }

            $inDb = isset($dbApexMap[$apex]);
            $inLegacy = isset($legacyApexMap[$apex]);

            if ($inDb || $inLegacy) {
                $apexWithOptionalWww[] = [
                    'apex' => $apex,
                    'vercel_names' => $group['names'],
                    'has_www' => $group['has_www'],
                    'www_redirect_correct' => $group['www_redirect_correct'],
                    'db_ids' => $inDb ? array_map(fn ($row) => $row->id, $dbApexMap[$apex]) : [],
                    'legacy_ids' => $inLegacy ? $legacyApexMap[$apex] : [],
                ];

                if ($group['has_www'] && ! $group['www_redirect_correct']) {
                    $incorrectRedirect[] = [
                        'apex' => $apex,
                        'vercel_name' => 'www.' . $apex,
                        'issue' => 'incorrect_www_redirect',
                    ];
                }

                if (! $group['has_www']) {
                    $apexWithoutWww[] = [
                        'apex' => $apex,
                        'type' => 'apex_without_www',
                    ];
                }

                continue;
            }

            $vercelOnlyOrphan[] = [
                'apex' => $apex,
                'vercel_names' => $group['names'],
            ];
        }

        foreach ($indexed as $apex => $group) {
            if ($group['wildcard'] || $group['platform']) {
                continue;
            }

            if ($group['has_www'] && ! isset($vercelSet[$apex])) {
                $wwwWithoutApex[] = [
                    'vercel_name' => 'www.' . $apex,
                    'missing' => $apex,
                    'type' => 'www_without_apex',
                ];
            }
        }

        $dbOnly = [];
        foreach ($dbRows as $row) {
            $apex = $this->client->normalizeApex((string) $row->custom_name);
            if ($apex === '' || $this->isWildcard($apex)) {
                continue;
            }

            if (! isset($vercelSet[$apex])) {
                $dbOnly[] = [
                    'id' => $row->id,
                    'custom_name' => $row->custom_name,
                    'apex' => $apex,
                    'status' => $row->status,
                ];
            }
        }

        $statusMismatch = [];
        foreach ($dbRows as $row) {
            if ($row->status !== 'active') {
                continue;
            }

            $dnsRecords = is_array($row->dns_records) ? $row->dns_records : [];
            $lastCheck = $dnsRecords['last_check'] ?? null;

            if (! is_array($lastCheck)) {
                continue;
            }

            if (($lastCheck['vercel_attached'] ?? null) === false) {
                $statusMismatch[] = [
                    'id' => $row->id,
                    'custom_name' => $row->custom_name,
                    'status' => $row->status,
                    'vercel_attached' => false,
                ];
            }
        }

        $legacyOrphans = DB::table('user_custom_domains as ucd')
            ->leftJoin('api_domains_settings as ads', 'ads.custom_domain_id', '=', 'ucd.id')
            ->whereNull('ads.id')
            ->select([
                'ucd.id',
                'ucd.user_id',
                'ucd.requested_domain',
                'ucd.current_domain',
            ])
            ->orderBy('ucd.id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'requested_domain' => $row->requested_domain,
                'current_domain' => $row->current_domain,
            ])
            ->all();

        return [
            'summary' => [
                'db_only' => count($dbOnly),
                'vercel_only_orphan' => count($vercelOnlyOrphan),
                'protected_platform' => count($protectedPlatform),
                'apex_with_optional_www' => count($apexWithOptionalWww),
                'www_without_apex' => count($wwwWithoutApex),
                'apex_without_www' => count($apexWithoutWww),
                'incorrect_redirect' => count($incorrectRedirect),
                'status_mismatch' => count($statusMismatch),
                'legacy_table_orphan' => count($legacyOrphans),
            ],
            'db_only' => $dbOnly,
            'vercel_only_orphan' => $vercelOnlyOrphan,
            'vercel_only' => $vercelOnlyOrphan,
            'protected_platform' => $protectedPlatform,
            'apex_with_optional_www' => $apexWithOptionalWww,
            'www_without_apex' => $wwwWithoutApex,
            'unpaired_www' => array_merge($wwwWithoutApex, $apexWithoutWww),
            'apex_without_www' => $apexWithoutWww,
            'incorrect_redirect' => $incorrectRedirect,
            'status_mismatch' => $statusMismatch,
            'legacy_table_orphan' => $legacyOrphans,
            'fetched_at' => $snapshot['fetched_at'] ?? null,
            'is_lower_bound' => (bool) ($snapshot['is_lower_bound'] ?? false),
        ];
    }

    /**
     * @return array{apex: string, status: string, error?: string}
     */
    public function removeVercelOnlyOrphan(
        string $apex,
        ?Request $request = null,
        string $confirmationField = 'confirm_domain',
        ?string $confirmedApex = null,
        ?string $actor = null
    ): array {
        $normalizedApex = $this->client->normalizeApex($apex);

        $this->mutationGuard->assertConfigured();
        $this->mutationGuard->assertEnvironmentAllowsMutations();
        $this->mutationGuard->assertProjectIdentity();

        if ($request !== null) {
            $this->mutationGuard->assertDestructiveDomainConfirmation(
                $request,
                $normalizedApex,
                $confirmationField
            );
        } elseif ($confirmedApex === null
            || $this->client->normalizeApex($confirmedApex) !== $normalizedApex) {
            throw new VercelDomainException(
                __('domain_mutation.confirmation_required', ['domain' => $normalizedApex]),
                internalCode: VercelDomainException::CODE_CONFIRMATION_REQUIRED
            );
        }

        if ($this->isPlatformDomain($normalizedApex)) {
            throw new VercelDomainException(
                __('domain_reconciliation.protected_platform', ['domain' => $normalizedApex]),
                internalCode: VercelDomainException::CODE_MUTATION_BLOCKED
            );
        }

        if ($this->isWildcard($normalizedApex)) {
            throw new VercelDomainException(
                __('domain_reconciliation.wildcard_not_supported', ['domain' => $normalizedApex]),
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        $snapshot = $this->cache->fresh();
        $report = $this->buildReportFromSnapshot($snapshot);
        $stillOrphan = collect($report['vercel_only_orphan'])
            ->first(fn (array $entry) => $entry['apex'] === $normalizedApex);

        if ($stillOrphan === null) {
            throw new VercelDomainException(
                __('domain_reconciliation.not_an_orphan', ['domain' => $normalizedApex]),
                internalCode: VercelDomainException::CODE_INVALID_DOMAIN
            );
        }

        try {
            $this->client->removeDomainWithRedirects($normalizedApex);

            Log::info('Vercel orphan domain removed', [
                'apex' => $normalizedApex,
                'actor' => $actor,
                'vercel_names' => $stillOrphan['vercel_names'] ?? [],
                'inventory_fetched_at' => $report['fetched_at'] ?? null,
                'inventory_lower_bound' => $report['is_lower_bound'] ?? false,
            ]);

            $this->cache->invalidateAdminCaches();

            return [
                'apex' => $normalizedApex,
                'status' => 'removed',
            ];
        } catch (Throwable $e) {
            Log::error('Vercel orphan domain removal failed', [
                'apex' => $normalizedApex,
                'actor' => $actor,
                'error' => $e->getMessage(),
            ]);

            $this->cache->invalidateAdminCaches();

            return [
                'apex' => $normalizedApex,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     * @param  array<string, true>  $platformSet
     * @return array<string, array{names: list<string>, platform: bool, wildcard: bool, has_www: bool, www_redirect_correct: bool}>
     */
    private function indexDomainsByApex(array $domains, array $platformSet): array
    {
        $groups = [];

        foreach ($domains as $domain) {
            if (! is_array($domain)) {
                continue;
            }

            $name = strtolower((string) ($domain['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            if ($this->isWildcard($name)) {
                $groups[$name] = [
                    'names' => [$name],
                    'platform' => false,
                    'wildcard' => true,
                    'has_www' => false,
                    'www_redirect_correct' => false,
                ];
                continue;
            }

            $isPlatform = isset($platformSet[$name]);
            $apex = str_starts_with($name, 'www.')
                ? substr($name, 4)
                : $this->client->normalizeApex($name);

            if ($apex === '') {
                continue;
            }

            if (! isset($groups[$apex])) {
                $groups[$apex] = [
                    'names' => [],
                    'platform' => $isPlatform || isset($platformSet[$apex]) || isset($platformSet['www.' . $apex]),
                    'wildcard' => false,
                    'has_www' => false,
                    'www_redirect_correct' => false,
                ];
            }

            $groups[$apex]['names'][] = $name;
            $groups[$apex]['platform'] = $groups[$apex]['platform'] || $isPlatform || isset($platformSet[$apex]) || isset($platformSet['www.' . $apex]);

            if (str_starts_with($name, 'www.')) {
                $groups[$apex]['has_www'] = true;
                $redirect = isset($domain['redirect']) ? strtolower((string) $domain['redirect']) : null;
                $statusCode = isset($domain['redirectStatusCode']) ? (int) $domain['redirectStatusCode'] : null;
                $targetOk = $redirect === $apex;
                $statusOk = $statusCode === null || in_array($statusCode, [301, 308], true);
                $groups[$apex]['www_redirect_correct'] = $targetOk && $statusOk && filled($domain['redirect'] ?? null);
            }
        }

        foreach ($groups as $apex => $group) {
            $groups[$apex]['names'] = array_values(array_unique($group['names']));
        }

        return $groups;
    }

    /**
     * @return array<string, list<int>>
     */
    private function buildLegacyApexMap(): array
    {
        $map = [];

        $rows = DB::table('user_custom_domains')
            ->select(['id', 'requested_domain', 'current_domain'])
            ->get();

        foreach ($rows as $row) {
            foreach ([$row->current_domain, $row->requested_domain] as $candidate) {
                if ($candidate === null || $candidate === '') {
                    continue;
                }

                $apex = $this->client->normalizeApex((string) $candidate);
                if ($apex === '' || $this->isWildcard($apex)) {
                    continue;
                }

                $map[$apex][] = (int) $row->id;
            }
        }

        foreach ($map as $apex => $ids) {
            $map[$apex] = array_values(array_unique($ids));
        }

        return $map;
    }

    /**
     * @return array<string, true>
     */
    private function platformDomainSet(): array
    {
        $set = [];

        foreach ((array) config('services.vercel.platform_domains', []) as $domain) {
            $normalized = strtolower(trim((string) $domain));
            if ($normalized !== '') {
                $set[$normalized] = true;
            }
        }

        return $set;
    }

    private function isPlatformDomain(string $apex): bool
    {
        $set = $this->platformDomainSet();

        return isset($set[$apex]) || isset($set['www.' . $apex]);
    }

    private function isWildcard(string $name): bool
    {
        return str_contains($name, '*');
    }
}
