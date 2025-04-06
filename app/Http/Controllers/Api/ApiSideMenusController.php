<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\BasicSetting;
use App\Models\Membership;
use App\Http\Helpers\LimitCheckerHelper;
use App\Http\Helpers\UserPermissionHelper;

class ApiSideMenusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return response()->json([

            'status' => true,
            'message' => 'Side menus retrieved successfully.',
            'code' => 200,
            'data' => [
                'sections' => [
                        [
                            'title' => 'لوحة التحكم',
                            'description' => 'نظره عامه عن الموقع',
                            'icon' => 'Settings2',
                            'path' => '/settings/side-menus/panel',
                        ],
                        [
                            'title' => 'ادارة المحتوى',
                            'description' => 'ادارة محتوى الموقع',
                            'icon' => 'SocialMedia',
                            'path' => '/settings/side-menus/content-settings',
                        ],
                        [
                            'title' => 'المدونة',
                            'description' => 'ادارة المدونة',
                            'icon' => 'Blog',
                            'icon' => 'Analytics',
                            'path' => '/settings/side-menus/blog',
                        ],
                        [
                            'title' => 'المشاريع',
                            'description' => 'ادارة المشاريع',
                            'icon' => 'Settings2',
                            'path' => '/settings/side-menus/projects',
                        ],
                        [
                            'title' => 'العقارات',
                            'description' => 'ادارة العقارات',
                            'icon' => 'Info',
                            'path' => '/settings/side-menus/properties',
                        ],
                        [
                            'title' => 'اعدادات الموقع',
                            'description' => 'تكوين اعدادات الموقع',
                            'icon' => 'Footer',
                            'path' => '/settings/side-menus/web-settings',
                        ]
                    ]
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
