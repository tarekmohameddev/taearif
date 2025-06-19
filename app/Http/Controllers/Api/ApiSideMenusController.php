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
        // Load the latest active membership for the user
        $membership = Membership::where('user_id', $user->id)->where('status', 1)->orderByDesc('id')->with('package')->first();

        // Get the package features
        $package = $membership?->package;

        // Default always-visible sections
        $sections = [
            [

                'title' => 'لوحة التحكم',
                'description' => 'نظره عامه عن الموقع',
                'icon' => 'panel',
                'path' => '/',
            ],
            [
                'title' => 'ادارة المحتوى',
                'description' => 'ادارة محتوى الموقع',
                'icon' => 'content-settings',
                'path' => '/content',
            ],
            [
                'title' => 'اعدادات الموقع',
                'description' => 'تكوين اعدادات الموقع',
                'icon' => 'web-settings',
                'path' => '/settings',
            ]
        ];

        // Conditionally add sections based on package
        if ($package) {
            if ($package->project_limit_number > 0) {
                $sections[] = [
                    'title' => 'المشاريع',
                    'description' => 'ادارة المشاريع',
                    'icon' => 'building',
                    'path' => '/projects',
                ];
            }

            if ($package->real_estate_limit_number > 0) {
                $sections[] = [
                    'title' => 'العقارات',
                    'description' => 'ادارة العقارات',
                    'icon' => 'home',
                    'path' => '/properties',
                ];
            }

            // You can add more conditional checks here for other modules
            if (!empty($package->features) && str_contains($package->features, 'Blog')) {
                $sections[] = [
                    'title' => 'المدونة',
                    'description' => 'ادارة المدونة',
                    'icon' => 'blog',
                    'path' => '/blog',
                ];
            }
        }

        $sections[] = [
                    'title' => 'التطبيقات',
                    'description' => 'ادارة تطبيقاتك',
                    'path' => '/apps',
                ];

        $whatsappMenu = ApiMenuItem::where('user_id', $user->id)
            ->where('url', '/whatsapp-ai')
            ->where('is_active', true)
            ->first();

        if ($whatsappMenu) {
            $sections[] = [
                'title' => $whatsappMenu->label ?? 'واتس اب',
                'description' => 'مساعد الذكاء الاصطناعي للواتس اب',
                'icon' => 'whatsapp',
                'path' => $whatsappMenu->url,
            ];
        }


        $aiMenu = ApiMenuItem::where('user_id', $user->id)
            ->where('url', '/ai')
            ->where('is_active', true)
            ->first();
        if ($aiMenu) {
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
            'data' => [
                'sections' => $sections,
            ],
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
