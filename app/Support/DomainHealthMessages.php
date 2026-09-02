<?php

declare(strict_types=1);

namespace App\Support;

final class DomainHealthMessages
{
    /** @var array<string, string> */
    private const MESSAGE_KEYS = [
        'Domain registration has expired.' => 'domain_health.sync.expired',
        'Verification checks are disabled (VERCEL_AUTO_ATTACH_CUSTOM_DOMAIN and VERCEL_CHECK_NAMESERVERS are false).' => 'domain_health.sync.checks_disabled_verbose',
        'Vercel domain integration is not configured.' => 'domain_health.sync.not_configured',
        'Domain could not be verified with the hosting provider yet.' => 'domain_health.sync.verify_failed',
        'Domain is not attached to the Vercel project.' => 'domain_health.sync.not_attached',
        'Domain is on Vercel but not verified yet. Ensure nameservers have propagated.' => 'domain_health.sync.not_verified',
        'Could not reach the hosting provider to check this domain.' => 'domain_health.sync.provider_unreachable',
        'Nameservers are not pointing to Vercel yet.' => 'domain_health.sync.ns_not_pointing',
        'Unable to resolve domain nameservers.' => 'domain_health.sync.ns_resolve_failed',
        'Domain is verified and nameservers are correct.' => 'domain_health.sync.verified_and_ns_ok',
        'Domain is verified on Vercel.' => 'domain_health.sync.verified_on_vercel',
        'Nameservers are correct.' => 'domain_health.sync.ns_ok',
        'Domain is no longer verified or nameservers changed.' => 'domain_health.sync.no_longer_verified',
        'Domain verification is still pending.' => 'domain_health.sync.still_pending',
        'Domain is verified, DNS is configured, and nameservers are correct.' => 'domain_health.sync.linked',
        'Apex domain is linked; optional www redirect is not configured.' => 'domain_health.sync.apex_only',
        'Add the ownership TXT record at your DNS provider to verify this domain.' => 'domain_health.sync.ownership_required',
        'DNS records are misconfigured according to the hosting provider.' => 'domain_health.sync.dns_misconfigured',
    ];

    public static function translate(string $message): string
    {
        if ($message === '') {
            return '';
        }

        $key = self::MESSAGE_KEYS[$message] ?? null;

        return $key !== null ? __($key) : $message;
    }
}
