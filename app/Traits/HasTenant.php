<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait HasTenant
{
    /**
     * Boot the trait and add global scope for tenant filtering
     */
    protected static function bootHasTenant()
    {
        // Global scope to filter by tenant
        static::addGlobalScope('tenant', function ($builder) {
            if (Auth::check()) {
                $user = Auth::user();
                $tenantId = $user->isTenant() ? $user->id : $user->tenant_id;
                if ($tenantId) {
                    $builder->where('user_id', $tenantId);
                }
            }
        });

        // Auto-set tenant ID when creating records
        static::creating(function ($model) {
            if (empty($model->user_id) && Auth::check()) {
                $user = Auth::user();
                $model->user_id = $user->isTenant() ? $user->id : $user->tenant_id;
            }
        });
    }

    /**
     * Scope to query records for a specific tenant
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope('tenant')->where('user_id', $tenantId);
    }

    /**
     * Relationship to the tenant (User)
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * Get the current tenant ID from the authenticated user
     */
    public static function getCurrentTenantId()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return $user->isTenant() ? $user->id : $user->tenant_id;
        }
        return null;
    }

    /**
     * Check if the current user can access this record
     */
    public function canAccess($user = null)
    {
        if (!$user) {
            $user = Auth::user();
        }
        
        if (!$user) {
            return false;
        }

        $userTenantId = $user->isTenant() ? $user->id : $user->tenant_id;
        return $this->user_id == $userTenantId;
    }
}
