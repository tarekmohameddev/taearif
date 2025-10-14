<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Membership;
use App\Models\Api\ApiMenuItem;
use Spatie\Permission\PermissionRegistrar;

class ApiSideMenusController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // ---- resolve team/tenant id and scope Spatie to it ----
        $teamId = $this->teamIdFor($user);
        app(PermissionRegistrar::class)->setPermissionsTeamId($teamId);

        // resolve owner (tenant for tenant; tenant for employee)
        $ownerId = $this->isTenant($user) ? (int) $user->id : (int) ($user->tenant_id ?? 0);

        // owners bypass permission checks
        $isOwner = $this->isTenant($user);
        $can = fn(string $perm) => $isOwner || $user->can($perm);

        // feature flags / package (use OWNER package for both tenant & employees)
        $isAffiliateApproved = $user->isAffiliateApproved();
        $membership = Membership::where('user_id', $ownerId)
            ->where('status', 1)
            ->orderByDesc('id')
            ->with('package')
            ->first();
        $package = $membership?->package;

        // (optional feature toggles stored in DB) — resolve by OWNER id
        $whatsappMenu = ApiMenuItem::where('user_id', $ownerId)
            ->where('url', '/whatsapp-ai')
            ->where('is_active', true)
            ->first();

        $aiMenu = ApiMenuItem::where('user_id', $ownerId)
            ->where('url', '/ai')
            ->where('is_active', true)
            ->first();

        // ---- declarative menu map (DRY) ----
        $c = [
            // Always show dashboard for all authenticated users
            [
                'perm'    => null, // no permission check needed
                'section' => ['title' => 'لوحة التحكم', 'description' => 'نظره عامه عن الموقع', 'icon' => 'panel', 'path' => '/'],
            ],
            [
                'perm'    => 'menu.content',
                'section' => ['title' => 'ادارة المحتوى', 'description' => 'ادارة محتوى الموقع', 'icon' => 'content-settings', 'path' => '/content'],
            ],
            [
                'perm'    => 'menu.settings',
                'section' => ['title' => 'اعدادات الموقع', 'description' => 'تكوين اعدادات الموقع', 'icon' => 'web-settings', 'path' => '/settings'],
            ],
            [
                'perm'    => 'menu.customers',
                'section' => ['title' => 'ادارة العملاء', 'description' => 'ادارة عملائك', 'icon' => 'users', 'path' => '/customers'],
            ],
            [
                'perm'    => 'menu.crm',
                'section' => ['title' => 'CRM', 'description' => 'تكوين اعدادات ادارة علاقات العملاء', 'icon' => 'crm', 'path' => '/crm'],
            ],
            // package + permission (package from OWNER)
            [
                'perm'    => 'menu.projects',
                'when'    => fn() => $package && ($package->project_limit_number > 0),
                'section' => ['title' => 'المشاريع', 'description' => ' ادارة المشاريع', 'icon' => 'building', 'path' => '/projects'],
            ],
            [
                'perm'    => 'menu.properties',
                'when'    => fn() => $package && ($package->real_estate_limit_number > 0),
                'section' => ['title' => 'العقارات', 'description' => 'ادارة العقارات', 'icon' => 'home', 'path' => '/properties'],
            ],
            [
                'perm'    => 'menu.projects',
                'when'    => fn() => $package && ($package->project_limit_number > 0),
                'section' => ['title' => 'محرر الموقع', 'description' => 'قم بأدارة تصميم الموقع', 'icon' => 'square-pen', 'path' => 'live-editor'],
            ],
            // [
            //     'perm'    => 'menu.blog',
            //     'when'    => fn() => $package && !empty($package->features) && str_contains($package->features, 'Blog'),
            //     'section' => ['title' => 'المدونة', 'description' => 'ادارة المدونة', 'icon' => 'blog', 'path' => '/blog'],
            // ],
            // Apps container
            // [
            //     'perm'    => 'menu.apps',
            //     'section' => ['title' => 'التطبيقات', 'description' => 'ادارة تطبيقاتك', 'icon' => 'apps', 'path' => '/apps'],
            // ],
            // Feature switches inside Apps (still check a perm)
            [
                'perm'    => 'menu.apps',
                'when'    => fn() => (bool) $whatsappMenu,
                'section' => ['title' => $whatsappMenu?->label ?? 'واتس اب', 'description' => 'مساعد الذكاء الاصطناعي للواتس اب', 'icon' => 'whatsapp', 'path' => $whatsappMenu?->url ?? '/whatsapp-ai'],
            ],
            [
                'perm'    => 'menu.apps',
                'when'    => fn() => (bool) $aiMenu,
                'section' => ['title' => $aiMenu?->label ?? 'الذكاء الاصطناعي', 'description' => 'مساعد الذكاء الاصطناعي', 'icon' => 'ai', 'path' => $aiMenu?->url ?? '/ai'],
            ],
            // Affiliate program
            [
                'perm'    => 'menu.affiliate',
                'when'    => fn() => (bool) $isAffiliateApproved,
                'section' => ['title' => 'برنامج الشراكة', 'description' => 'إدارة برنامج العمولة', 'icon' => 'lucide lucide-user-check h-5 w-5 text-primary', 'path' => '/affiliate'],
            ],
        ];

        $sections = [];
        foreach ($c as $item) {
            // Dashboard shows for all users (no permission check)
            if ($item['perm'] === null) {
                $sections[] = $item['section'];
                continue;
            }
            
            // Other items check permission + optional conditions
            if ($can($item['perm']) && (!isset($item['when']) || $item['when']() === true)) {
                $sections[] = $item['section'];
            }
        }

        return response()->json([
            'status'  => true,
            'message' => 'Side menus retrieved successfully.',
            'code'    => 200,
            'data'    => ['sections' => $sections],
        ]);
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
