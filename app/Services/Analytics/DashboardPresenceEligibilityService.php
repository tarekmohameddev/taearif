<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;

class DashboardPresenceEligibilityService
{
    public function resolve(User $user): array
    {
        if ($this->isImpersonated($user)) {
            return $this->rejected('impersonated');
        }

        if (! $this->isEligibleDashboardUser($user)) {
            return $this->rejected('ineligible_user');
        }

        if ($user->isTenant()) {
            return [
                'eligible' => true,
                'tenant_owner_id' => (int) $user->id,
                'reason' => null,
            ];
        }

        $tenantOwnerId = $user->tenantOwnerId();

        if ($tenantOwnerId <= 0) {
            return $this->rejected('invalid_tenant_owner');
        }

        $tenantOwner = $user->relationLoaded('tenant')
            ? $user->tenant
            : User::query()->withTrashed()->find($tenantOwnerId);

        if (! $tenantOwner || ! $tenantOwner->isTenant() || ! $this->isEligibleDashboardUser($tenantOwner)) {
            return $this->rejected('invalid_tenant_owner');
        }

        return [
            'eligible' => true,
            'tenant_owner_id' => $tenantOwnerId,
            'reason' => null,
        ];
    }

    private function isEligibleDashboardUser(User $user): bool
    {
        if (! $user->isTenant() && ! $user->isEmployee()) {
            return false;
        }

        if ((int) $user->status !== 1) {
            return false;
        }

        if (! (bool) $user->active) {
            return false;
        }

        return ! $user->trashed();
    }

    private function isImpersonated(User $user): bool
    {
        if (! (bool) config('dashboard-presence.exclude_impersonation', true)) {
            return false;
        }

        $token = $user->currentAccessToken();

        if (! $token || ! method_exists($token, 'getAttribute')) {
            return false;
        }

        $tokenName = (string) $token->getAttribute('name');

        return $tokenName !== '' && str_starts_with($tokenName, 'impersonated-by-admin-');
    }

    private function rejected(string $reason): array
    {
        return [
            'eligible' => false,
            'tenant_owner_id' => null,
            'reason' => $reason,
        ];
    }
}
