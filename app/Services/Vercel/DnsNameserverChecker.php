<?php

namespace App\Services\Vercel;

class DnsNameserverChecker
{
    /**
     * @param  list<string>  $expected
     */
    /**
     * @return list<string>
     */
    public function getObservedNameservers(string $domain): array
    {
        $apex = strtolower(preg_replace('#^www\.#', '', trim($domain)));
        $records = @dns_get_record($apex, DNS_NS);

        if ($records === false || $records === []) {
            return [];
        }

        $found = [];
        foreach ($records as $record) {
            $target = isset($record['target']) ? strtolower(rtrim((string) $record['target'], '.')) : '';
            if ($target !== '') {
                $found[] = $target;
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * @param  list<string>  $expected
     */
    public function hasExpectedNameservers(string $domain, array $expected): bool
    {
        $found = $this->getObservedNameservers($domain);
        if ($found === []) {
            return false;
        }

        $expectedNormalized = array_map(
            static fn (string $ns) => strtolower(rtrim($ns, '.')),
            $expected
        );

        foreach ($expectedNormalized as $ns) {
            if (! in_array($ns, $found, true)) {
                return false;
            }
        }

        return true;
    }
}
