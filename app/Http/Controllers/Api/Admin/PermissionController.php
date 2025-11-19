<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Admin\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Permission Controller
 *
 * Handles permission-related endpoints for admin API
 */
class PermissionController extends BaseController
{
    /**
     * Get all available permissions in the system
     * 
     * Dynamically extracts unique permissions from all roles and admins
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        // Get all unique permissions from roles table
        $permissions = Role::query()
            ->whereNotNull('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique()
            ->values()
            ->sort()
            ->toArray();

        // Also check admins table for employee-specific permissions
        $employeePermissions = DB::table('admins')
            ->whereNotNull('permissions')
            ->get()
            ->pluck('permissions')
            ->map(function ($permissions) {
                return is_string($permissions) 
                    ? json_decode($permissions, true) 
                    : $permissions;
            })
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        // Merge and get unique permissions
        $allPermissions = collect($permissions)
            ->merge($employeePermissions)
            ->unique()
            ->values()
            ->sort()
            ->toArray();

        // If no permissions found in database, fallback to default list
        if (empty($allPermissions)) {
            $allPermissions = $this->getDefaultPermissions();
        }

        return $this->successResponse([
            'permissions' => $allPermissions,
        ], 'Permissions retrieved successfully');
    }

    /**
     * Get default permissions list (fallback)
     *
     * @return array
     */
    private function getDefaultPermissions(): array
    {
        return [
            'Dashboard',
            'Settings',
            'Registered Users',
            'Admins Management',
            'Packages',
            'Payment Log',
            'Custom Domains',
            'Subdomains',
            'Blogs',
            'FAQ Management',
            'Contact Page',
            'Footer',
            'Home Page',
            'Language Management',
            'Menu Builder',
            'Pages',
            'Subscribers',
            'Announcement Popup',
            'Role Management',
        ];
    }
}

