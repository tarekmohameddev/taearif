<?php

namespace App\Services\Vercel;

class DomainHealthPolicy
{
    /**
     * @param  array<string, mixed>  $evidence
     * @return array{dns_mode: string, health_code: string, servable: bool, severity: string, retryable: bool, database_status: string, remediation_key: string}
     */
    public function evaluate(array $evidence): array
    {
        $mode = $this->resolveDnsMode($evidence['dns_mode'] ?? null);
        $code = $this->resolveHealthCode($evidence, $mode);
        $servable = in_array($code, ['linked', 'apex_only'], true);
        $retryable = in_array($code, [
            'provider_error',
            'certificate_pending',
            'unverified',
            'ns_not_pointing',
            'zone_disabled',
            'ownership_required',
            'unchecked',
        ], true);

        return [
            'dns_mode' => $mode,
            'health_code' => $code,
            'servable' => $servable,
            'severity' => $this->severityFor($code),
            'retryable' => $retryable,
            'database_status' => $servable ? 'active' : ($code === 'certificate_error' || $code === 'invalid_domain' || $code === 'expired' ? 'failed' : 'pending'),
            'remediation_key' => "domain_diagnostics.action.{$code}",
        ];
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function resolveHealthCode(array $evidence, ?string $mode = null): string
    {
        $mode = $this->resolveDnsMode($mode ?? ($evidence['dns_mode'] ?? null));
        $autoAttach = (bool) ($evidence['auto_attach_custom_domain'] ?? true);
        $nsCheckEnabled = (bool) ($evidence['nameserver_check_enabled'] ?? true);

        if (! $autoAttach && ! $nsCheckEnabled) {
            return 'checks_disabled';
        }

        if (($evidence['reason'] ?? null) === 'expired') {
            return 'expired';
        }

        if (($evidence['reason'] ?? null) === 'invalid_domain') {
            return 'invalid_domain';
        }

        if ($this->providerStateUnknown($evidence)) {
            return 'provider_error';
        }

        $attached = (bool) ($evidence['apex_attached'] ?? $evidence['vercel_attached'] ?? false);
        if (! $attached) {
            return 'not_on_vercel';
        }

        $verified = (bool) ($evidence['apex_verified'] ?? $evidence['vercel_verified'] ?? false);
        $ownershipChallenge = $evidence['ownership_challenge'] ?? null;
        if (is_array($ownershipChallenge) && $ownershipChallenge !== [] && ! $verified) {
            return 'ownership_required';
        }

        if ((bool) ($evidence['dns_misconfigured'] ?? false)) {
            return 'dns_misconfigured';
        }

        if ($mode === 'vercel_ns') {
            $accountDomainPresent = (bool) ($evidence['account_domain_present'] ?? false);
            $zoneEnabled = (bool) ($evidence['zone_enabled'] ?? false);
            if ($accountDomainPresent && ! $zoneEnabled) {
                return 'zone_disabled';
            }

            if ($nsCheckEnabled && (bool) ($evidence['nameservers_ok'] ?? false) !== true) {
                return 'ns_not_pointing';
            }
        } else {
            $apexMatches = $evidence['apex_matches_recommended'] ?? null;
            if ($apexMatches === false) {
                return 'dns_misconfigured';
            }
        }

        if (! $verified) {
            return 'unverified';
        }

        $apexReadiness = (string) ($evidence['apex_certificate_readiness'] ?? $evidence['certificate_readiness'] ?? '');
        if ($apexReadiness === 'certificate_error') {
            return 'certificate_error';
        }

        if ($this->hasExplicitApexSslEvidence($evidence)
            && ($evidence['apex_ssl_ready'] ?? $evidence['ssl_ready'] ?? false) !== true) {
            return 'certificate_pending';
        }

        $wwwPresent = (bool) ($evidence['www_present'] ?? false);
        $wwwRedirectCorrect = (bool) ($evidence['www_redirect_correct'] ?? false);
        $wwwDnsHealthy = $mode === 'external_dns'
            ? (($evidence['www_matches_recommended'] ?? null) === true)
            : true;
        $wwwSslReady = (bool) ($evidence['www_ssl_ready'] ?? $evidence['apex_ssl_ready'] ?? $evidence['ssl_ready'] ?? false);

        if (! $wwwPresent || ! $wwwRedirectCorrect || ! $wwwDnsHealthy || ! $wwwSslReady) {
            return 'apex_only';
        }

        return 'linked';
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    private function hasExplicitApexSslEvidence(array $evidence): bool
    {
        return array_key_exists('apex_ssl_ready', $evidence)
            || array_key_exists('ssl_ready', $evidence)
            || filled($evidence['apex_certificate_readiness'] ?? null)
            || filled($evidence['certificate_readiness'] ?? null);
    }

    public function resolveDnsMode(mixed $mode): string
    {
        return in_array($mode, ['vercel_ns', 'external_dns'], true) ? (string) $mode : 'vercel_ns';
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    private function providerStateUnknown(array $evidence): bool
    {
        if (($evidence['reason'] ?? null) === 'provider_error') {
            return true;
        }

        if (array_key_exists('provider_reachable', $evidence) && $evidence['provider_reachable'] === false) {
            return true;
        }

        $mode = $this->resolveDnsMode($evidence['dns_mode'] ?? null);

        $apexLookupUnknown = array_key_exists('apex_dns_lookup_unknown', $evidence)
            ? (bool) $evidence['apex_dns_lookup_unknown']
            : (bool) ($evidence['dns_lookup_unknown'] ?? false);

        if (($evidence['provider_error'] ?? false) === true) {
            return true;
        }

        // Public apex record lookups are authoritative for external-DNS domains,
        // but Vercel-nameserver health can still be determined from Vercel and NS
        // evidence after a www-only mutation even when the public apex lookup is
        // temporarily unavailable.
        if ($mode === 'external_dns' && $apexLookupUnknown) {
            return true;
        }

        $message = (string) ($evidence['message'] ?? '');

        return str_contains($message, 'Could not reach the hosting provider')
            || str_contains($message, 'Unable to resolve domain nameservers');
    }

    private function severityFor(string $code): string
    {
        return match ($code) {
            'linked', 'apex_only' => 'success',
            'ownership_required', 'dns_misconfigured', 'ns_not_pointing', 'unverified', 'zone_disabled', 'certificate_pending' => 'warning',
            'not_on_vercel', 'certificate_error', 'invalid_domain', 'expired' => 'danger',
            default => 'secondary',
        };
    }

    public function severityForCode(string $code): string
    {
        return $this->severityFor($code);
    }
}
