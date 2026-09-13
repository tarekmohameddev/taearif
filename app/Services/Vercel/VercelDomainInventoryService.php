<?php

namespace App\Services\Vercel;

class VercelDomainInventoryService
{
    public function __construct(
        private readonly VercelDomainClient $client
    ) {
    }

    public function client(): VercelDomainClient
    {
        return $this->client;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSnapshot(bool $fetchFresh = false): array
    {
        $this->client->assertConfigured();

        $listed = $this->client->listProjectDomains();
        $metrics = $this->computeMetrics($listed['domains'], (bool) $listed['is_lower_bound']);

        return [
            'names' => $listed['names'],
            'domains' => $listed['domains'],
            'count' => $listed['count'],
            'is_lower_bound' => $listed['is_lower_bound'],
            'metrics' => $metrics,
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     * @return array<string, int|bool|null>
     */
    public function computeMetrics(array $domains, bool $isLowerBound): array
    {
        $platformDomains = $this->platformDomainSet();
        $apexNamesOnProject = [];

        foreach ($domains as $domain) {
            $name = (string) ($domain['name'] ?? '');
            if ($name !== '' && ! str_starts_with($name, 'www.')) {
                $apexNamesOnProject[$name] = true;
            }
        }

        $totalEntries = count($domains);
        $apexEntries = 0;
        $wwwRedirects = 0;
        $verifiedEntries = 0;
        $unverifiedEntries = 0;
        $platformEntries = 0;
        $customerApex = 0;
        $unpairedRedirects = 0;
        $mismatchedRedirects = 0;

        foreach ($domains as $domain) {
            $name = (string) ($domain['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $isPlatform = isset($platformDomains[$name]);
            $isWww = str_starts_with($name, 'www.');
            $hasRedirect = filled($domain['redirect'] ?? null);
            $verified = (bool) ($domain['verified'] ?? false);

            if ($verified) {
                $verifiedEntries++;
            } else {
                $unverifiedEntries++;
            }

            if ($isPlatform) {
                $platformEntries++;
            }

            if ($isWww) {
                if ($hasRedirect) {
                    $wwwRedirects++;
                    $expectedApex = substr($name, 4);
                    $redirectTarget = strtolower((string) $domain['redirect']);
                    $statusCode = isset($domain['redirectStatusCode']) ? (int) $domain['redirectStatusCode'] : null;

                    if ($redirectTarget !== $expectedApex) {
                        $mismatchedRedirects++;
                    } elseif ($statusCode !== null && ! in_array($statusCode, [301, 308], true)) {
                        $mismatchedRedirects++;
                    } elseif (! isset($apexNamesOnProject[$expectedApex])) {
                        $unpairedRedirects++;
                    }
                } else {
                    $unpairedRedirects++;
                }

                continue;
            }

            $apexEntries++;

            if (! $isPlatform) {
                $customerApex++;
            }

            if ($hasRedirect) {
                $mismatchedRedirects++;
            }
        }

        $maxEntries = config('services.vercel.max_project_domains');
        $freeEntries = null;

        if ($maxEntries !== null && ! $isLowerBound) {
            $freeEntries = max(0, (int) $maxEntries - $totalEntries);
        }

        return [
            'total_entries' => $totalEntries,
            'free_entries' => $freeEntries,
            'apex_entries' => $apexEntries,
            'www_redirects' => $wwwRedirects,
            'verified_entries' => $verifiedEntries,
            'unverified_entries' => $unverifiedEntries,
            'platform_entries' => $platformEntries,
            'customer_apex' => $customerApex,
            'unpaired_redirects' => $unpairedRedirects,
            'mismatched_redirects' => $mismatchedRedirects,
            'is_lower_bound' => $isLowerBound,
        ];
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

    public function apexPresentInSnapshot(array $snapshot, string $apex): bool
    {
        $apex = strtolower(trim($apex));

        return in_array($apex, $snapshot['names'] ?? [], true);
    }

    public function wwwPresentInSnapshot(array $snapshot, string $apex): bool
    {
        $apex = strtolower(trim($apex));

        return in_array('www.' . $apex, $snapshot['names'] ?? [], true);
    }

    /**
     * Slots required to provision apex only (tenant store flow).
     * Optional www is admin-enabled separately and is not counted here.
     */
    public function requiredSlotsForApex(array $snapshot, string $apex): int
    {
        if ($this->apexPresentInSnapshot($snapshot, $apex)) {
            return 0;
        }

        return 1;
    }

    /**
     * @return array{allowed: bool, reason: ?string}
     */
    public function evaluateCapacityForApex(array $snapshot, string $apex): array
    {
        if ($snapshot['is_lower_bound'] ?? false) {
            return [
                'allowed' => false,
                'reason' => 'inventory_lower_bound',
            ];
        }

        $requiredSlots = $this->requiredSlotsForApex($snapshot, $apex);
        if ($requiredSlots === 0) {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        $freeEntries = $snapshot['metrics']['free_entries'] ?? null;
        if ($freeEntries === null) {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        if ($freeEntries >= $requiredSlots) {
            return [
                'allowed' => true,
                'reason' => null,
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'capacity_reached',
        ];
    }
}
