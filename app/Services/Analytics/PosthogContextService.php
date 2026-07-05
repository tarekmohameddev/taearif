<?php

namespace App\Services\Analytics;

use App\Models\User;

class PosthogContextService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.posthog.enabled')
            && ! empty(config('services.posthog.key'));
    }

    /**
     * Client-safe PostHog config for browser / SPA bootstrap.
     *
     * @return array{enabled: bool, host?: string, api_key?: string}
     */
    public function clientConfig(): array
    {
        if (! $this->isEnabled()) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'host' => config('services.posthog.host'),
            'api_key' => config('services.posthog.key'),
        ];
    }

    /**
     * PostHog identify + group context for an authenticated tenant SPA user.
     */
    public function forUser(User $user): array
    {
        $config = $this->clientConfig();
        if (! $config['enabled']) {
            return $config;
        }

        $tenantOwnerId = (int) $user->tenantOwnerId();
        $tenantOwner = null;

        if ($user->isEmployee() && $tenantOwnerId > 0) {
            $tenantOwner = User::query()->find($tenantOwnerId);
        } elseif ($user->isTenant()) {
            $tenantOwner = $user;
        }

        $properties = [
            'email' => $user->email,
            'username' => $user->username,
            'account_type' => $user->account_type ?? 'tenant',
            'tenant_owner_id' => $tenantOwnerId,
            'surface' => 'tenant_app',
        ];

        $groups = [];
        if ($tenantOwnerId > 0) {
            $groupProperties = [];
            if ($tenantOwner) {
                $groupProperties['username'] = $tenantOwner->username;
                if (! empty($tenantOwner->company_name)) {
                    $groupProperties['company_name'] = $tenantOwner->company_name;
                }
            }

            $groups[] = [
                'type' => 'tenant',
                'key' => (string) $tenantOwnerId,
                'properties' => $groupProperties,
            ];
        }

        return array_merge($config, [
            'distinct_id' => 'user:'.$user->id,
            'properties' => $properties,
            'groups' => $groups,
        ]);
    }
}
