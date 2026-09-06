<?php

namespace App\Services\Vercel;

use App\Contracts\Vercel\VercelDomainSourceOfTruth;
use App\Domain\Domain\Models\CustomDomain;
use App\Exceptions\BusinessLogicException;
use App\Models\Api\ApiDomainSetting;

/**
 * Enforces the two-model boundary: legacy `user_custom_domains` operations cannot
 * detach or overwrite domains that are backed by {@see VercelDomainSourceOfTruth}.
 */
class VercelBackedDomainGuard
{
    public function __construct(
        private readonly VercelDomainClient $client
    ) {
    }

    public function assertLegacyDeleteSafe(CustomDomain $domain): void
    {
        if ($this->findVercelBackedSetting($domain) !== null) {
            $this->reject(
                'This domain is managed through the Vercel-backed domain settings table and cannot be deleted via the legacy admin API.',
                'VERCEL_BACKED_DOMAIN_DELETE_BLOCKED'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assertLegacyUpdateSafe(CustomDomain $domain, array $data): void
    {
        $setting = $this->findVercelBackedSetting($domain);

        if ($setting === null) {
            return;
        }

        $domainFields = ['requested_domain', 'current_domain', 'status', 'user_id'];
        $touchingDomainIdentity = false;

        foreach ($domainFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $incoming = $data[$field];
            $current = $domain->getAttribute($field);

            if ($field === 'status') {
                $incoming = filter_var($incoming, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                $current = (bool) $current;
            }

            if ($incoming !== $current) {
                $touchingDomainIdentity = true;
                break;
            }
        }

        if ($touchingDomainIdentity) {
            $this->reject(
                'This domain is managed through api_domains_settings (Vercel source of truth). Update the linked API domain instead of overwriting legacy fields.',
                'VERCEL_BACKED_DOMAIN_UPDATE_BLOCKED'
            );
        }
    }

    public function assertLegacyStatusMutationSafe(CustomDomain $domain): void
    {
        if ($this->findVercelBackedSetting($domain) !== null) {
            $this->reject(
                'This domain status is managed through the Vercel-backed domain settings table.',
                'VERCEL_BACKED_DOMAIN_STATUS_BLOCKED'
            );
        }
    }

    private function findVercelBackedSetting(CustomDomain $domain): ?ApiDomainSetting
    {
        $linked = $domain->relationLoaded('apiDomainSetting')
            ? $domain->apiDomainSetting
            : $domain->apiDomainSetting()->first();

        if ($linked !== null) {
            return $linked;
        }

        $apexCandidates = array_values(array_unique(array_filter([
            $this->client->normalizeApex((string) $domain->current_domain),
            $this->client->normalizeApex((string) $domain->requested_domain),
        ], fn (string $value) => $value !== '')));

        if ($apexCandidates === []) {
            return null;
        }

        return ApiDomainSetting::query()
            ->where('user_id', $domain->user_id)
            ->whereIn('custom_name', $apexCandidates)
            ->first();
    }

    /**
     * @return never
     */
    private function reject(string $message, string $code): void
    {
        throw new BusinessLogicException($message, $code, 422);
    }
}
