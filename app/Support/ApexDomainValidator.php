<?php

namespace App\Support;

class ApexDomainValidator
{
    private const LABEL_PATTERN = '/^[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?$/';

    /**
     * @var list<string>
     */
    private const MULTI_PART_PUBLIC_SUFFIXES = [
        'co.uk',
        'com.au',
        'co.jp',
        'com.sa',
        'net.sa',
        'org.uk',
        'co.nz',
        'com.br',
        'co.za',
        'com.mx',
    ];

    public static function normalize(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = rtrim($domain, '/');
        $domain = rtrim($domain, '.');
        $domain = preg_replace('#^www\.#', '', $domain) ?? $domain;

        return $domain;
    }

    /**
     * @return string|null Error message when invalid, null when valid.
     */
    public static function validate(string $normalized): ?string
    {
        if ($normalized === '') {
            return 'Domain name is required.';
        }

        if (str_contains($normalized, '..')) {
            return 'Domain contains empty labels.';
        }

        if (filter_var($normalized, FILTER_VALIDATE_IP)) {
            return 'IP addresses are not valid domain names.';
        }

        if (str_starts_with($normalized, '[') && str_ends_with($normalized, ']')) {
            return 'IP addresses are not valid domain names.';
        }

        if (! preg_match('/^[a-z0-9.-]+$/', $normalized)) {
            return 'Domain contains invalid characters.';
        }

        $labels = explode('.', $normalized);
        if (count($labels) < 2) {
            return 'Domain must include a valid public suffix (e.g. example.com).';
        }

        foreach ($labels as $label) {
            if ($label === '') {
                return 'Domain contains empty labels.';
            }

            if (strlen($label) > 63) {
                return 'Each domain label must be 63 characters or fewer.';
            }

            if (! preg_match(self::LABEL_PATTERN, $label)) {
                return 'Domain contains invalid label formatting.';
            }
        }

        $tld = $labels[count($labels) - 1];
        if (strlen($tld) < 2 || preg_match('/^[0-9]+$/', $tld)) {
            return 'Domain must have a valid top-level domain.';
        }

        if (in_array($normalized, self::MULTI_PART_PUBLIC_SUFFIXES, true)) {
            return 'Domain must include a name before the public suffix.';
        }

        $lastTwo = count($labels) >= 2
            ? $labels[count($labels) - 2] . '.' . $labels[count($labels) - 1]
            : '';

        if (in_array($lastTwo, self::MULTI_PART_PUBLIC_SUFFIXES, true) && count($labels) < 3) {
            return 'Domain must include a name before the public suffix.';
        }

        return null;
    }
}
