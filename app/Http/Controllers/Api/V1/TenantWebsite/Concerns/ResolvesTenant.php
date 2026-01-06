<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite\Concerns;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

trait ResolvesTenant
{
    /**
     * Resolve tenant by identifier.
     *
     * Supports:
     * - Username (tenant subdomain/slug)
     * - Custom domain (with or without protocol / leading "www.")
     */
    protected function resolveTenant(Request $request, string $tenantId): User
    {
        $tenantId = trim(strtolower($tenantId));

        // 1) Username
        $tenant = User::query()->where('username', $tenantId)->first();
        if ($tenant) {
            return $tenant;
        }

        // 2) Custom domain
        $domainCandidates = $this->domainCandidates($tenantId);
        $domainRecord = ApiDomainSetting::query()
            ->whereIn('custom_name', $domainCandidates)
            ->where(function ($q) {
                // some environments may have different status semantics
                $q->whereNull('status')
                    ->orWhere('status', 'active')
                    ->orWhere('status', 1);
            })
            ->first();

        if ($domainRecord?->user) {
            return $domainRecord->user;
        }

        // 3) If tenant was already resolved from the request host, allow it only
        // when the provided tenantId matches the resolved tenant (username or domain).
        $hostTenant = $request->attributes->get('tenant_user');
        if ($hostTenant instanceof User) {
            if (strtolower((string) $hostTenant->username) === $tenantId) {
                return $hostTenant;
            }

            $matchesDomain = ApiDomainSetting::query()
                ->where('user_id', $hostTenant->id)
                ->whereIn('custom_name', $domainCandidates)
                ->exists();

            if ($matchesDomain) {
                return $hostTenant;
            }
        }

        throw (new ModelNotFoundException())->setModel(User::class);
    }

    private function domainCandidates(string $value): array
    {
        $normalized = $this->normalizeDomain($value);
        $withoutWww = preg_replace('#^www\.#', '', $normalized);

        return array_values(array_unique(array_filter([
            $normalized,
            $withoutWww,
            'www.' . $withoutWww,
        ])));
    }

    private function normalizeDomain(string $value): string
    {
        // Strip protocol
        $value = preg_replace('#^https?://#', '', $value);
        // Strip leading www.
        $value = preg_replace('#^www\.#', '', $value);
        // Remove trailing slashes and whitespace
        return rtrim(trim(strtolower($value)), '/');
    }
}


