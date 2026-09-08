<?php

namespace App\Services\Vercel;

class DomainDnsRecordService
{
    /**
     * @param  list<string>|mixed  $recommendedIpv4
     * @param  list<string>|mixed  $recommendedCname
     * @return array<string, mixed>
     */
    public function inspect(string $apex, mixed $recommendedIpv4 = [], mixed $recommendedCname = []): array
    {
        $apex = strtolower(preg_replace('#^www\.#', '', trim($apex)));
        $www = 'www.' . $apex;

        $normalizedIpv4 = $this->normalizeValues($recommendedIpv4);
        $normalizedCname = $this->normalizeValues($recommendedCname);

        $apexLookup = $this->lookup($apex);
        $wwwLookup = $this->lookup($www);

        return [
            'apex_records' => $apexLookup['records'],
            'www_records' => $wwwLookup['records'],
            'apex_addresses' => $apexLookup['addresses'],
            'apex_cnames' => $apexLookup['cnames'],
            'www_addresses' => $wwwLookup['addresses'],
            'www_cnames' => $wwwLookup['cnames'],
            'apex_matches_recommended' => $this->matchApex($apexLookup, $normalizedIpv4, $normalizedCname),
            'www_matches_recommended' => $this->matchWww($wwwLookup, $normalizedIpv4, $normalizedCname),
            'apex_lookup_known' => $apexLookup['known'],
            'www_lookup_known' => $wwwLookup['known'],
            'dns_provider_reachable' => $apexLookup['known'],
            'dns_lookup_unknown' => ! $apexLookup['known'],
            'apex_dns_lookup_unknown' => ! $apexLookup['known'],
            'www_dns_lookup_unknown' => ! $wwwLookup['known'],
        ];
    }

    /**
     * @return array{known: bool, records: list<array<string, string>>, addresses: list<string>, cnames: list<string>}
     */
    private function lookup(string $host): array
    {
        try {
            $records = @dns_get_record($host, DNS_A + DNS_AAAA + DNS_CNAME);
        } catch (\Throwable) {
            $records = false;
        }

        if ($records === false) {
            return [
                'known' => false,
                'records' => [],
                'addresses' => [],
                'cnames' => [],
            ];
        }

        $normalized = [];
        $addresses = [];
        $cnames = [];

        foreach ($records as $record) {
            if (! is_array($record)) {
                continue;
            }

            $type = strtoupper((string) ($record['type'] ?? ''));
            if ($type === 'A') {
                $value = trim((string) ($record['ip'] ?? ''));
            } elseif ($type === 'AAAA') {
                $value = strtolower(trim((string) ($record['ipv6'] ?? '')));
            } elseif ($type === 'CNAME') {
                $value = strtolower(rtrim(trim((string) ($record['target'] ?? '')), '.'));
            } else {
                continue;
            }

            if ($value === '') {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'value' => $value,
            ];

            if ($type === 'CNAME') {
                $cnames[] = $value;
            } else {
                $addresses[] = $value;
            }
        }

        return [
            'known' => true,
            'records' => array_values($normalized),
            'addresses' => array_values(array_unique($addresses)),
            'cnames' => array_values(array_unique($cnames)),
        ];
    }

    /**
     * @param  array{known: bool, addresses: list<string>, cnames: list<string>}  $lookup
     * @param  list<string>  $recommendedIpv4
     * @param  list<string>  $recommendedCname
     */
    private function matchApex(array $lookup, array $recommendedIpv4, array $recommendedCname): ?bool
    {
        if (! $lookup['known']) {
            return null;
        }

        if ($recommendedIpv4 === [] && $recommendedCname === []) {
            return null;
        }

        if (array_intersect($lookup['addresses'], $recommendedIpv4) !== []) {
            return true;
        }

        if (array_intersect($lookup['cnames'], $recommendedCname) !== []) {
            return true;
        }

        return false;
    }

    /**
     * @param  array{known: bool, addresses: list<string>, cnames: list<string>}  $lookup
     * @param  list<string>  $recommendedIpv4
     * @param  list<string>  $recommendedCname
     */
    private function matchWww(array $lookup, array $recommendedIpv4, array $recommendedCname): ?bool
    {
        if (! $lookup['known']) {
            return null;
        }

        if ($recommendedCname !== [] && array_intersect($lookup['cnames'], $recommendedCname) !== []) {
            return true;
        }

        if ($recommendedIpv4 !== [] && array_intersect($lookup['addresses'], $recommendedIpv4) !== []) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>|mixed  $values
     * @return list<string>
     */
    private function normalizeValues(mixed $values): array
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        $normalized = [];

        foreach ($values as $value) {
            if (is_array($value)) {
                foreach ($value as $nested) {
                    if (is_string($nested) && trim($nested) !== '') {
                        $normalized[] = strtolower(rtrim(trim($nested), '.'));
                    }
                }

                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                $normalized[] = strtolower(rtrim(trim($value), '.'));
            }
        }

        return array_values(array_unique($normalized));
    }
}
