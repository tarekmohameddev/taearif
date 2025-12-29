<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Membership;
use App\Models\Api\ApiMenuItem;

class ApiSideMenusController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Eager load relationships to avoid N+1 queries
        $user->loadMissing('affiliateUser');

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

        // Combine the two ApiMenuItem queries into one to reduce database calls
        $menuItems = ApiMenuItem::where('user_id', $ownerId)
            ->whereIn('url', ['/whatsapp-ai', '/ai'])
            ->where('is_active', true)
            ->get()
            ->keyBy('url');
        
        $whatsappMenu = $menuItems->get('/whatsapp-ai');
        $aiMenu = $menuItems->get('/ai');

        // ---- declarative menu map (DRY) ----
        $c = [
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
                'when'    => fn() => $package && ($package->project_limit_number > 0),
                'section' => ['title' => 'المشاريع', 'description' => ' ادارة المشاريع', 'icon' => 'building', 'path' => '/projects'],
            ],
            [
                'perm'    => 'properties.view',
                'when'    => fn() => $package && ($package->real_estate_limit_number > 0),
                'section' => ['title' => 'العقارات', 'description' => 'ادارة العقارات', 'icon' => 'home', 'path' => '/properties'],
            ],
            [
                'perm'    => 'properties.view',
                'when'    => fn() => $package && ($package->real_estate_limit_number > 0),
                'section' => ['title' => 'طلبات العملاء', 'description' => 'ادارة طلبات العملاء العقارية', 'icon' => 'home', 'path' => '/property-requests'],
            ],
            [
                'perm'    => 'properties.view',
                'when'    => fn() => $package && ($package->real_estate_limit_number > 0),
                'section' => ['title' => 'مركز توافق الطلبات الذكائي', 'description' => 'احصل على توافق ذكي مع الطلبات', 'icon' => 'sparkles', 'path' => '/matching'],
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
            // [
            //     'perm'    => 'apps.view',
            //     'when'    => fn() => (bool) $whatsappMenu,
            //     'section' => ['title' => $whatsappMenu?->label ?? 'واتس اب', 'description' => 'مساعد الذكاء الاصطناعي للواتس اب', 'icon' => 'whatsapp', 'path' => $whatsappMenu?->url ?? '/whatsapp-ai'],
            // ],
            // [
            //     'perm'    => 'apps.view',
            //     'when'    => fn() => (bool) $aiMenu,
            //     'section' => ['title' => $aiMenu?->label ?? 'الذكاء الاصطناعي', 'description' => 'مساعد الذكاء الاصطناعي', 'icon' => 'ai', 'path' => $aiMenu?->url ?? '/ai'],
            // ],
            // Affiliate program
            [
                'perm'    => 'affiliate.view',
                'when'    => fn() => (bool) $isAffiliateApproved,
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
