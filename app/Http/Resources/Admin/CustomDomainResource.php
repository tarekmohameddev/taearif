<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomDomainResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = $this->whenLoaded('user', fn () => $this->user);
        $domain = $this->current_domain ?: $this->requested_domain;

        $apiDomain = $this->whenLoaded('apiDomainSetting', fn () => $this->apiDomainSetting, null);

        $legacyStatusValue = $this->getOriginal('status');
        if ($legacyStatusValue === null) {
            $legacyStatusValue = $this->status ? 1 : 0;
        }

        $legacyStatusKey = match ((int) $legacyStatusValue) {
            0 => 'pending',
            1 => 'active',
            2 => 'rejected',
            3 => 'removed',
            default => 'inactive',
        };

        $statusKey = $apiDomain && $apiDomain->status
            ? $apiDomain->status
            : ($this->isApproved()
                ? 'active'
                : ($this->isPending() ? 'pending' : $legacyStatusKey));

        $sslEnabled = $apiDomain?->ssl;
        $sslStatus = is_null($sslEnabled)
            ? null
            : ($sslEnabled ? 'enabled' : ($apiDomain && $apiDomain->status === 'pending' ? 'provisioning' : 'disabled'));
        $addedDate = $apiDomain?->added_date ?? $apiDomain?->created_at;
        $statusSource = $apiDomain ? 'api' : 'legacy';

        $tenant = $this->when($user, function () use ($user) {
            if (!$user) {
                return null;
            }

            $fullName = trim(
                (($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
            );
            $siteName = optional($user->generalSettings)->site_name;
            $displayName = $siteName
                ?: ($fullName !== ''
                    ? $fullName
                    : ($user->company_name ?? $user->username));

            return [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'name' => $displayName,
                'site_name' => $siteName,
                'company_name' => $user->company_name,
            ];
        });

        $tenantData = $tenant ?? [];

        $tenantData = $tenant ?? [];
        $isActive = $statusKey === 'active';

        return [
            'id' => $this->id,
            'domain' => $domain,
            'requested_domain' => $this->requested_domain,
            'current_domain' => $this->current_domain,
            'user_id' => data_get($tenantData, 'user_id'),
            'username' => data_get($tenantData, 'username'),
            'email' => data_get($tenantData, 'email'),
            'name' => data_get($tenantData, 'name'),
            'site_name' => data_get($tenantData, 'site_name'),
            'company_name' => data_get($tenantData, 'company_name'),
            'status_key' => $statusKey,
            'status_label' => $statusKey, // front-end can localize
            'status_detail' => $apiDomain?->status ?? $legacyStatusKey,
            'status_source' => $statusSource,
            'status' => $isActive,
            'is_active' => $isActive,
            'is_approved' => $this->isApproved(),
            'is_pending' => $statusKey === 'pending',
            'ssl_enabled' => is_null($sslEnabled) ? null : (bool) $sslEnabled,
            'ssl_status' => $sslStatus,
            'ssl_last_updated' => optional($apiDomain?->updated_at)->toIso8601String(),
            'is_primary' => (bool) ($apiDomain?->primary ?? false),
            'added_date' => optional($addedDate)->toIso8601String(),
            'expires_at' => optional($apiDomain?->expires_at)->toIso8601String(),
            'registrar' => $apiDomain?->registrar,
            'auto_renewal' => $apiDomain?->auto_renewal,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}

