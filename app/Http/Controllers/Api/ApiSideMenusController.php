<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Membership;
use App\Models\Api\ApiMenuItem;

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

        // ---- declarative menu map (DRY) ----
        $menuConfig = [
            // Always show dashboard for all authenticated users
            [
                'perm'    => null, // no permission check needed
                'section' => ['title' => 'لوحة التحكم', 'description' => 'نظره عامه عن الموقع', 'icon' => 'panel', 'path' => '/'],
            ],
            [
                'perm'    => 'settings.view',
                'section' => ['title' => 'اعدادات الموقع', 'description' => 'تكوين اعدادات الموقع', 'icon' => 'web-settings', 'path' => '/settings'],
            ],
            [
                'perm'    => 'customers.view',
                'section' => ['title' => 'ادارة العملاء', 'description' => 'ادارة عملائك', 'icon' => 'users', 'path' => '/customers'],
            ],
            [
                'perm'    => 'crm.view',
                'section' => ['title' => 'CRM', 'description' => 'تكوين اعدادات ادارة علاقات العملاء', 'icon' => 'crm', 'path' => '/crm'],
            ],
            // package + permission (package from OWNER)
            [
                'perm'    => 'projects.view',
                'when'    => $hasProjects,
                'section' => ['title' => 'المشاريع', 'description' => ' ادارة المشاريع', 'icon' => 'building', 'path' => '/projects'],
            ],
            [
                'perm'    => 'properties.view',
                'when'    => $hasProperties,
                'section' => ['title' => 'العقارات', 'description' => 'ادارة العقارات', 'icon' => 'home', 'path' => '/properties'],
            ],
            [
                'perm'    => 'properties.view',
                'when'    => $hasProperties,
                'section' => ['title' => 'طلبات العملاء', 'description' => 'ادارة طلبات العملاء العقارية', 'icon' => 'home', 'path' => '/property-requests'],
            ],
            [
                'perm'    => 'properties.view',
                'when'    => $hasProperties,
                'section' => ['title' => 'مركز توافق الطلبات الذكائي', 'description' => 'احصل على توافق ذكي مع الطلبات', 'icon' => 'sparkles', 'path' => '/matching'],
            ],
            // Affiliate program
            [
                'perm'    => 'affiliate.view',
                'when'    => $isAffiliateApproved,
                'section' => ['title' => 'برنامج الشراكة', 'description' => 'إدارة برنامج العمولة', 'icon' => 'lucide lucide-user-check h-5 w-5 text-primary', 'path' => '/affiliate'],
            ],
            [
                'perm'    => 'content.view',
                'section' => ['title' => 'مدير الواتساب', 'description' => 'اضف ارقام واتساب', 'icon' => 'message-square-share', 'path' => '/whatsapp-center'],
            ],
            [
                'perm'    => 'content.view',
                'section' => ['title' => 'تعديل تصميم الموقع', 'description' => 'ادارة محتوى الموقع', 'icon' => 'content-settings', 'path' => 'live-editor'],
            ],
            [
                'perm'    => 'content.view',
                'section' => ['title' => 'ادارة الموظفين', 'description' => 'ادارة الموظفين', 'icon' => 'message-square-share', 'path' => '/access-control'],
            ],
            [
                'perm'    => 'content.view',
                'section' => ['title' => 'ادارة الايجارات', 'description' => 'ادارة ايجارتك', 'icon' => 'message-square-share', 'path' => '/rental-management'],
            ],
        ];

        $sections = [];
        foreach ($menuConfig as $item) {
            // Dashboard shows for all users (no permission check)
            if ($item['perm'] === null) {
                $sections[] = $item['section'];
                continue;
            }

            // Check permission
            if (!$can($item['perm'])) {
                continue;
            }

            // Check optional condition (now a boolean, not a closure)
            if (isset($item['when']) && $item['when'] !== true) {
                continue;
            }

            $sections[] = $item['section'];
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
}
