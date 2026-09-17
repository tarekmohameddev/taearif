<?php

namespace App\Services\Vercel;

use App\Models\Api\ApiDomainSetting;

final class DomainDnsActionPlanService
{
    /**
     * Build provider-ready instructions without performing DNS or provider writes.
     *
     * @param  array<string, mixed>  $diagnostics
     * @return array<string, mixed>
     */
    public function build(array $diagnostics): array
    {
        $mode = (string) ($diagnostics['dns_mode'] ?? ApiDomainSetting::DNS_MODE_VERCEL_NS);
        $observedNameservers = $this->normalizeValues($diagnostics['observed_nameservers'] ?? []);
        $expectedNameservers = $this->normalizeValues($diagnostics['expected_nameservers'] ?? []);

        if ($mode !== ApiDomainSetting::DNS_MODE_EXTERNAL_DNS) {
            $nameserversMatch = $expectedNameservers !== []
                && count(array_diff($expectedNameservers, $observedNameservers)) === 0;

            return [
                'mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
                'nameserver_action' => $nameserversMatch ? 'keep' : 'replace',
                'nameservers' => $expectedNameservers,
                'observed_nameservers' => $observedNameservers,
                'rows' => [],
                'has_changes' => ! $nameserversMatch,
                'lookup_complete' => true,
            ];
        }

        $ipv4Groups = DomainDnsRecommendationService::normalizeGroups(
            $diagnostics['recommended_ipv4_groups'] ?? [],
            $diagnostics['recommended_ipv4'] ?? []
        );
        $cnameGroups = DomainDnsRecommendationService::normalizeGroups(
            $diagnostics['recommended_cname_groups'] ?? [],
            $diagnostics['recommended_cname'] ?? []
        );

        $ipv4Groups = DomainDnsRecommendationService::withFallback(
            $ipv4Groups,
            $diagnostics['recommended_a_default'] ?? null
        );
        $cnameGroups = DomainDnsRecommendationService::withFallback(
            $cnameGroups,
            $diagnostics['recommended_cname_default'] ?? null
        );

        $apexRecords = $this->normalizeRecords($diagnostics['apex_records'] ?? []);
        $wwwRecords = $this->normalizeRecords($diagnostics['www_records'] ?? []);
        $apexKnown = $this->resolveLookupKnown($diagnostics, 'apex_lookup_known', $apexRecords);
        $wwwKnown = $this->resolveLookupKnown($diagnostics, 'www_lookup_known', $wwwRecords);

        $apexValues = $this->valuesForType($apexRecords, 'A');
        $wwwCnames = $this->valuesForType($wwwRecords, 'CNAME');
        [$selectedIpv4, $ipv4Matched] = $this->selectGroup($ipv4Groups, $apexValues, true);
        [$selectedCname, $cnameMatched] = $this->selectGroup($cnameGroups, $wwwCnames, false);

        $rows = array_merge(
            $this->planApex($apexRecords, $selectedIpv4['values'] ?? [], $apexKnown),
            $this->planWww($wwwRecords, $selectedCname['values'] ?? [], $wwwKnown)
        );

        $ownershipChallenge = $diagnostics['ownership_challenge'] ?? null;
        if (is_array($ownershipChallenge) && filled($ownershipChallenge['value'] ?? null)) {
            $rows[] = [
                'action' => 'ensure',
                'type' => strtoupper((string) ($ownershipChallenge['type'] ?? 'TXT')),
                'host' => $this->relativeHost(
                    (string) ($ownershipChallenge['domain'] ?? $ownershipChallenge['name'] ?? ''),
                    (string) ($diagnostics['custom_name'] ?? '')
                ),
                'value' => (string) $ownershipChallenge['value'],
                'ttl' => 'auto',
            ];
        }

        $hasChanges = collect($rows)->contains(
            static fn (array $row): bool => in_array($row['action'], ['add', 'delete', 'replace', 'ensure'], true)
        );

        return [
            'mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'nameserver_action' => 'keep',
            'nameservers' => $observedNameservers,
            'observed_nameservers' => $observedNameservers,
            'rows' => $rows,
            'has_changes' => $hasChanges,
            'lookup_complete' => $apexKnown && $wwwKnown,
            'selected_ipv4_rank' => $selectedIpv4['rank'] ?? null,
            'selected_cname_rank' => $selectedCname['rank'] ?? null,
            'kept_existing_ipv4_group' => $ipv4Matched,
            'kept_existing_cname_group' => $cnameMatched,
        ];
    }

    /**
     * @param  list<array{rank: int, values: list<string>}>  $groups
     * @param  list<string>  $observed
     * @return array{0: array{rank: int, values: list<string>}|null, 1: bool}
     */
    private function selectGroup(array $groups, array $observed, bool $requireAll): array
    {
        foreach ($groups as $group) {
            $values = $group['values'] ?? [];
            $matches = $requireAll
                ? $values !== [] && array_diff($values, $observed) === []
                : array_intersect($values, $observed) !== [];

            if ($matches) {
                if (! $requireAll) {
                    $matchingValue = array_values(array_intersect($values, $observed))[0];
                    $group['values'] = [$matchingValue];
                }

                return [$group, true];
            }
        }

        $group = $groups[0] ?? null;
        if ($group !== null && ! $requireAll && count($group['values']) > 1) {
            $group['values'] = [array_values($group['values'])[0]];
        }

        return [$group, false];
    }

    /**
     * @param  list<array{type: string, value: string}>  $records
     * @param  list<string>  $desired
     * @return list<array{action: string, type: string, host: string, value: string, ttl: string}>
     */
    private function planApex(array $records, array $desired, bool $known): array
    {
        if (! $known) {
            return array_map(fn (string $value): array => $this->row('ensure', 'A', '@', $value), $desired);
        }

        $rows = [];
        $actualA = $this->valuesForType($records, 'A');
        foreach ($records as $record) {
            if (! in_array($record['type'], ['A', 'AAAA', 'CNAME'], true)) {
                continue;
            }

            $keep = $record['type'] === 'A' && in_array($record['value'], $desired, true);
            $rows[] = $this->row($keep ? 'keep' : 'delete', $record['type'], '@', $record['value']);
        }

        foreach (array_diff($desired, $actualA) as $value) {
            $rows[] = $this->row('add', 'A', '@', $value);
        }

        return $this->sortRows($rows);
    }

    /**
     * @param  list<array{type: string, value: string}>  $records
     * @param  list<string>  $desired
     * @return list<array{action: string, type: string, host: string, value: string, ttl: string}>
     */
    private function planWww(array $records, array $desired, bool $known): array
    {
        $target = $desired[0] ?? null;
        if ($target === null) {
            return [];
        }

        if (! $known) {
            return [$this->row('ensure', 'CNAME', 'www', $target)];
        }

        // Resolvers commonly return a CNAME followed by the target's A records.
        // When a CNAME exists, only that owner record belongs in the registrar UI.
        $hasCname = $this->valuesForType($records, 'CNAME') !== [];
        if ($hasCname) {
            $records = array_values(array_filter(
                $records,
                static fn (array $record): bool => $record['type'] === 'CNAME'
            ));
        }

        $rows = [];
        $actualCnames = $this->valuesForType($records, 'CNAME');
        foreach ($records as $record) {
            if (! in_array($record['type'], ['A', 'AAAA', 'CNAME'], true)) {
                continue;
            }

            $keep = $record['type'] === 'CNAME' && $record['value'] === $target;
            $rows[] = $this->row($keep ? 'keep' : 'delete', $record['type'], 'www', $record['value']);
        }

        if (! in_array($target, $actualCnames, true)) {
            $rows[] = $this->row('add', 'CNAME', 'www', $target);
        }

        return $this->sortRows($rows);
    }

    /** @return array{action: string, type: string, host: string, value: string, ttl: string} */
    private function row(string $action, string $type, string $host, string $value): array
    {
        return compact('action', 'type', 'host', 'value') + ['ttl' => 'auto'];
    }

    /**
     * @param  list<array{action: string, type: string, host: string, value: string, ttl: string}>  $rows
     * @return list<array{action: string, type: string, host: string, value: string, ttl: string}>
     */
    private function sortRows(array $rows): array
    {
        $priority = ['delete' => 0, 'add' => 1, 'ensure' => 2, 'keep' => 3];
        usort($rows, static fn (array $left, array $right): int =>
            ($priority[$left['action']] ?? 9) <=> ($priority[$right['action']] ?? 9)
        );

        return $rows;
    }

    /** @return list<array{type: string, value: string}> */
    private function normalizeRecords(mixed $records): array
    {
        if (! is_array($records)) {
            return [];
        }

        $normalized = [];
        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $type = strtoupper(trim((string) ($record['type'] ?? '')));
            $value = strtolower(rtrim(trim((string) ($record['value'] ?? '')), '.'));
            if ($type !== '' && $value !== '') {
                $normalized[$type . '|' . $value] = compact('type', 'value');
            }
        }

        return array_values($normalized);
    }

    /** @param  list<array{type: string, value: string}>  $records */
    private function valuesForType(array $records, string $type): array
    {
        return array_values(array_unique(array_map(
            static fn (array $record): string => $record['value'],
            array_filter($records, static fn (array $record): bool => $record['type'] === $type)
        )));
    }

    /** @return list<string> */
    private function normalizeValues(mixed $values): array
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => is_scalar($value)
                ? strtolower(rtrim(trim((string) $value), '.'))
                : '',
            $values
        ))));
    }

    /** @param  list<array{type: string, value: string}>  $records */
    private function resolveLookupKnown(array $diagnostics, string $key, array $records): bool
    {
        if (array_key_exists($key, $diagnostics) && $diagnostics[$key] !== null) {
            return (bool) $diagnostics[$key];
        }

        return $records !== [];
    }

    private function relativeHost(string $host, string $apex): string
    {
        $host = strtolower(rtrim(trim($host), '.'));
        $apex = strtolower(rtrim(trim($apex), '.'));
        if ($host === $apex) {
            return '@';
        }
        if ($apex !== '' && str_ends_with($host, '.' . $apex)) {
            return substr($host, 0, -strlen('.' . $apex));
        }

        return $host !== '' ? $host : '@';
    }
}
