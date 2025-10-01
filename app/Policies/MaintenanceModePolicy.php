<?php

namespace App\Policies;

use App\Models\User;
use App\Services\MembershipService;

class MaintenanceModePolicy
{
    protected $membershipService;
    
    public function __construct(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }
    
    /**
     * Determine if user can control maintenance mode
     */
    public function control(User $user): bool
    {
        return $this->membershipService->canControlMaintenanceMode($user);
    }
    
    /**
     * Determine if user can disable maintenance mode
     */
    public function disable(User $user): bool
    {
        return $this->membershipService->canControlMaintenanceMode($user);
    }
    
    /**
     * Determine if user can enable maintenance mode
     */
    public function enable(User $user): bool
    {
        return $this->membershipService->canControlMaintenanceMode($user);
    }
    
    /**
     * Determine if user can toggle maintenance mode
     */
    public function toggle(User $user): bool
    {
        return $this->membershipService->canControlMaintenanceMode($user);
    }
    
    /**
     * Get the reason why user cannot control maintenance mode
     */
    public function getRestrictionReason(User $user): ?string
    {
        if ($this->membershipService->hasFreePackage($user)) {
            return 'Free package users cannot control maintenance mode. Please upgrade your package to access this feature.';
        }
        
        return null;
    }
}
