<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Membership;
use App\Models\Api\ApiMenuItem;
use App\Models\Api\ApiApp;
use App\Models\Api\ApiInstallation;
use App\Models\Api\ApiSidebarItem;
use App\Enums\InstallStatus;

class ApiSideMenusController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Eager load relationships to avoid N+1 queries (including roles.permissions for faster permission checks)
        $user->loadMissing(['affiliateUser', 'roles.permissions']);

        // resolve owner (tenant for tenant; tenant for employee)
        $ownerId = $this->isTenant($user) ? (int) $user->id : (int) ($user->tenant_id ?? 0);
        $isOwner = $this->isTenant($user);
        $isAffiliateApproved = $user->isAffiliateApproved();

        // Cache key includes user ID and factors that affect the menu
        $cacheKey = "side_menus:v1:{$user->id}:{$ownerId}:" . ($isOwner ? '1' : '0') . ':' . ($isAffiliateApproved ? '1' : '0');

        $sections = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user, $ownerId, $isOwner, $isAffiliateApproved) {
            return $this->buildMenuSections($user, $ownerId, $isOwner, $isAffiliateApproved);
        });

        return response()->json([
            'status'  => true,
            'message' => 'Side menus retrieved successfully.',
            'code'    => 200,
            'data'    => ['sections' => $sections],
        ])->header('Cache-Control', 'private, max-age=300');
    }

    /**
     * Build the menu sections based on user permissions and package features.
     */
    private function buildMenuSections($user, int $ownerId, bool $isOwner, bool $isAffiliateApproved): array
    {
        // Cache membership and package data separately (shared across users of same tenant)
        $package = Cache::remember("membership_package:{$ownerId}", 300, function () use ($ownerId) {
            $membership = Membership::where('user_id', $ownerId)
                ->where('status', 1)
                ->orderByDesc('id')
                ->with('package')
                ->first();
            return $membership?->package;
        });

        // Pre-compute all permission checks at once to avoid repeated lookups
        $allPermissions = ['settings.view', 'customers.view', 'crm.view', 'projects.view', 'properties.view', 'affiliate.view', 'content.view'];
        $userPermissions = [];
        
        if ($isOwner) {
            // Owners have all permissions
            foreach ($allPermissions as $perm) {
                $userPermissions[$perm] = true;
            }
        } else {
            // Check permissions once for employees
            foreach ($allPermissions as $perm) {
                $userPermissions[$perm] = $user->can($perm);
            }
        }

        $can = fn(string $perm) => $userPermissions[$perm] ?? false;

        // Package feature checks
        $hasProjects = $package && ($package->project_limit_number > 0);
        $hasProperties = $package && ($package->real_estate_limit_number > 0);

        // Get sidebar items from database
        $sidebarItems = ApiSidebarItem::active()->ordered()->get();

        $sections = [];
        foreach ($sidebarItems as $item) {
            // Check permission (if null, show for all authenticated users)
            if ($item->permission !== null && !$can($item->permission)) {
                continue;
            }

            // Check condition type
            $conditionMet = true;
            if ($item->condition_type !== null) {
                switch ($item->condition_type) {
                    case 'has_projects':
                        $conditionMet = $hasProjects;
                        break;
                    case 'has_properties':
                        $conditionMet = $hasProperties;
                        break;
                    case 'is_affiliate_approved':
                        $conditionMet = $isAffiliateApproved;
                        break;
                }
            }

            if (!$conditionMet) {
                continue;
            }

            // Add item to sections
            $icon = $item->icon;
            if (!str_contains($icon, ' ') && !str_starts_with($icon, 'fa') && !str_starts_with($icon, 'flaticon')) {
                $icon = 'flaticon-' . $icon;
            }

            $sections[] = [
                'title' => $item->title,
                'description' => $item->description,
                'icon' => $icon,
                'path' => $item->path,
            ];
        }

        // Add installed apps to the sidebar
        $installedApps = $this->getInstalledApps($user->id);
        foreach ($installedApps as $app) {
            $sections[] = [
                'title' => $app['name'],
                'description' => $app['description'] ?? '',
                'icon' => 'app', // Default icon for apps, can be customized per app
                'path' => $app['path'],
                'type' => 'app', // Indicate this is an app
                'app_id' => $app['id'],
                'img' => $app['img'] ?? null,
            ];
        }

        return $sections;
    }

    // ---- helpers ----
    private function teamIdFor($user): int
    {
        if ($this->isTenant($user)) {
            return (int) $user->id;
        }
        // employees
        return (int) ($user->tenant_id ?? 0);
    }

    private function isTenant($user): bool
    {
        return method_exists($user, 'isTenant') ? $user->isTenant() : (($user->account_type ?? 'tenant') === 'tenant');
    }

    /**
     * Get installed apps for the user
     *
     * @param int $userId
     * @return array
     */
    private function getInstalledApps(int $userId): array
    {
        // Cache installed apps per user
        return Cache::remember("installed_apps:{$userId}", now()->addMinutes(5), function () use ($userId) {
            $installations = ApiInstallation::where('user_id', $userId)
                ->whereIn('status', [InstallStatus::Installed->value, InstallStatus::Trialing->value])
                ->whereHas('app', function ($query) {
                    $query->where('is_enabled', true)
                          ->whereNotNull('path')
                          ->where('path', '!=', '');
                })
                ->with('app')
                ->get();

            return $installations->map(function ($installation) {
                $app = $installation->app;
                return [
                    'id' => $app->id,
                    'name' => $app->name,
                    'description' => $app->description,
                    'path' => $app->path,
                    'img' => $app->img,
                ];
            })->toArray();
        });
    }
}
