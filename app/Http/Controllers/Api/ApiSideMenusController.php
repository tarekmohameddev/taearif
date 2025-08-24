<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\BasicSetting;
use App\Models\Membership;
use App\Http\Helpers\LimitCheckerHelper;
use App\Http\Helpers\UserPermissionHelper;
use Illuminate\Support\Facades\Auth;
use App\Models\Api\ApiMenuItem;
use Illuminate\Support\Facades\Log;

use App\Models\Api\ApiAffiliateUser;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

class ApiSideMenusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


public function index()
{
    $user = Auth::user();

    // IMPORTANT: scope Spatie to the tenant team for this request
    app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenantOwnerId());

    $isOwner = method_exists($user, 'isTenant') ? $user->isTenant() : (($user->account_type ?? 'tenant') === 'tenant');
    $can = fn(string $perm) => $isOwner || $user->can($perm);

    // Affiliate & Packages as you had
    $isAffiliateApproved = $user->isAffiliateApproved();
    $membership = Membership::where('user_id', $user->id)->where('status', 1)->orderByDesc('id')->with('package')->first();
    $package = $membership?->package;

    $sections = [];

    // Dashboard
    if ($can('menu.dashboard')) {
        $sections[] = [
            'title' => 'لوحة التحكم',
            'description' => 'نظره عامه عن الموقع',
            'icon' => 'panel',
            'path' => '/',
        ];
    }

    // Content
    if ($can('menu.content')) {
        $sections[] = [
            'title' => 'ادارة المحتوى',
            'description' => 'ادارة محتوى الموقع',
            'icon' => 'content-settings',
            'path' => '/content',
        ];
    }

    // Settings
    if ($can('menu.settings')) {
        $sections[] = [
            'title' => 'اعدادات الموقع',
            'description' => 'تكوين اعدادات الموقع',
            'icon' => 'web-settings',
            'path' => '/settings',
        ];
    }

    // Customers
    if ($can('menu.customers')) {
        $sections[] = [
            'title' => 'ادارة العملاء',
            'description' => 'ادارة عملائك',
            'icon' => 'web-settings',
            'path' => '/customers',
        ];
    }

    // CRM (if you want it gated separately)
    if ($can('menu.crm')) {
        $sections[] = [
            'title' => 'crm',
            'description' => 'تكوين اعدادات ادارة علاقات العملاء',
            'icon' => 'web-settings',
            'path' => '/settings',
        ];
    }

    // Projects (package + permission)
    if ($package && $package->project_limit_number > 0 && $can('menu.projects')) {
        $sections[] = [
            'title' => 'المشاريع',
            'description' => 'ادارة المشاريع',
            'icon' => 'building',
            'path' => '/projects',
        ];
    }

    // Properties (package + permission)
    if ($package && $package->real_estate_limit_number > 0 && $can('menu.properties')) {
        $sections[] = [
            'title' => 'العقارات',
            'description' => 'ادارة العقارات',
            'icon' => 'home',
            'path' => '/properties',
        ];
    }

    // Blog (package + permission)
    if ($package && !empty($package->features) && str_contains($package->features, 'Blog') && $can('menu.blog')) {
        $sections[] = [
            'title' => 'المدونة',
            'description' => 'ادارة المدونة',
            'icon' => 'blog',
            'path' => '/blog',
        ];
    }

    // Apps base (if you want a section)
    if ($can('menu.apps')) {
        $sections[] = [
            'title' => 'التطبيقات',
            'description' => 'ادارة تطبيقاتك',
            'path' => '/apps',
        ];
    }

    // WhatsApp AI (feature switch + permission)
    $whatsappMenu = ApiMenuItem::where('user_id', $user->id)->where('url', '/whatsapp-ai')->where('is_active', true)->first();
    if ($whatsappMenu && $can('menu.apps')) {
        $sections[] = [
            'title' => $whatsappMenu->label ?? 'واتس اب',
            'description' => 'مساعد الذكاء الاصطناعي للواتس اب',
            'icon' => 'whatsapp',
            'path' => $whatsappMenu->url,
        ];
    }

    // Affiliate (feature flag + permission)
    if ($isAffiliateApproved && $can('menu.affiliate')) {
        $sections[] = [
            'title' => 'برنامج الشراكة',
            'description' => 'إدارة برنامج العمولة',
            'icon' => 'lucide lucide-user-check h-5 w-5 text-primary',
            'path' => '/affiliate',
        ];
    }

    // AI (feature switch + permission)
    $aiMenu = ApiMenuItem::where('user_id', $user->id)->where('url', '/ai')->where('is_active', true)->first();
    if ($aiMenu && $can('menu.apps')) {
        $sections[] = [
            'title' => $aiMenu->label ?? 'الذكاء الاصطناعي',
            'description' => 'مساعد الذكاء الاصطناعي',
            'icon' => 'ai',
            'path' => $aiMenu->url,
        ];
    }

    return response()->json([
        'status' => true,
        'message' => 'Side menus retrieved successfully.',
        'code' => 200,
        'data' => ['sections' => $sections],
    ]);
}


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
