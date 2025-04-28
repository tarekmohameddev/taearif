<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Membership;
use Illuminate\Http\Request;
use App\Models\Api\ApiMenuItem;
use App\Models\Api\ApiMenuSetting;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ApiMenuController extends Controller
{
/**
     * Get menu items and settings for the authenticated user
     */
    public function index(Request $request)
    {
        $user_id = $request->user()->id;

        $menuItems = ApiMenuItem::where('user_id', $user_id)->orderBy('order')->get();
        $settings = ApiMenuSetting::where('user_id', $user_id)->first();

        if ($menuItems->isEmpty()) {
            $membership = Membership::where('user_id', $user_id)
                ->where('status', 'active') // You might adjust this condition
                ->with('package')
                ->first();

            $defaultItems = [
                [
                    'label' => 'الرئيسية',
                    'url' => '/',
                    'is_external' => false,
                    'is_active' => true,
                    'order' => 1,
                    'parent_id' => null,
                    'show_on_mobile' => true,
                    'show_on_desktop' => true,
                ],
                [
                    'label' => 'من نحن',
                    'url' => '/about',
                    'is_external' => false,
                    'is_active' => true,
                    'order' => 2,
                    'parent_id' => null,
                    'show_on_mobile' => true,
                    'show_on_desktop' => true,
                ],
            ];

            if ($membership && $membership->package && !empty($membership->package->real_estate_limit_number)) {
                $defaultItems[] = [
                    'label' => 'الوحدات',
                    'is_external' => false,
                    'is_active' => true,
                    'order' => 3,
                    'parent_id' => null,
                    'show_on_mobile' => true,
                    'show_on_desktop' => true,
                ];
            }

            if ($membership && $membership->package && !empty($membership->package->project_limit_number)) {
                $defaultItems[] = [
                    'label' => 'المشاريع',
                    'url' => '/projects',
                    'is_external' => false,
                    'is_active' => true,
                    'order' => 4,
                    'parent_id' => null,
                    'show_on_mobile' => true,
                    'show_on_desktop' => true,
                ];
            }

            $defaultItems[] = [
                'label' => 'اتصل بنا',
                'url' => '/contact',
                'is_external' => false,
                'is_active' => true,
                'order' => 5,
                'parent_id' => null,
                'show_on_mobile' => true,
                'show_on_desktop' => true,
            ];

            foreach ($defaultItems as $item) {
                $menuItem = new ApiMenuItem($item);
                $menuItem->user_id = $user_id;
                $menuItem->save();
            }

            $menuItems = ApiMenuItem::where('user_id', $user_id)->orderBy('order')->get();
        }

        if (!$settings) {
            $settings = ApiMenuSetting::create([
                'user_id' => $user_id,
                'menu_position' => 'top',
                'menu_style' => 'standard',
                'mobile_menu_type' => 'hamburger',
                'is_sticky' => true,
                'is_transparent' => false,
            ]);
        }

        $formattedItems = $menuItems->map(function ($item) {
            return [
                'id' => $item->id,
                'label' => $item->label,
                'url' => $item->url,
                'isExternal' => $item->is_external,
                'isActive' => $item->is_active,
                'order' => $item->order,
                'parentId' => $item->parent_id,
                'showOnMobile' => $item->show_on_mobile,
                'showOnDesktop' => $item->show_on_desktop,
            ];
        });

        $formattedSettings = [
            'menuPosition' => $settings->menu_position,
            'menuStyle' => $settings->menu_style,
            'mobileMenuType' => $settings->mobile_menu_type,
            'isSticky' => $settings->is_sticky,
            'isTransparent' => $settings->is_transparent,
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'menuItems' => $formattedItems,
                'settings' => $formattedSettings,
            ]
        ]);
    }


    /**
     * Update menu items and settings for the authenticated user
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menuItems' => 'required|array',
            'menuItems.*.id' => 'required|integer',
            'menuItems.*.label' => 'required|string',
            'menuItems.*.url' => 'required|string',
            'menuItems.*.isExternal' => 'required|boolean',
            'menuItems.*.isActive' => 'required|boolean',
            'menuItems.*.order' => 'required|integer',
            'menuItems.*.parentId' => 'nullable|integer',
            'menuItems.*.showOnMobile' => 'required|boolean',
            'menuItems.*.showOnDesktop' => 'required|boolean',

            'settings' => 'required|array',
            'settings.menuPosition' => 'nullable|string|in:top,bottom,left,right',
            'settings.menuStyle' => 'nullable|string|in:buttons,underline,minimal,standard,default',
            'settings.mobileMenuType' => 'nullable|string|in:hamburger,sidebar,fullscreen',
            'settings.isSticky' => 'nullable|boolean',
            'settings.isTransparent' => 'nullable|boolean',

        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user_id = $request->user();
        $user_id = $user_id->id;

        $menuItemsData = $request->menuItems;
        $settingsData = $request->settings;


        DB::beginTransaction();

        try {

            ApiMenuItem::where('user_id', $user_id)->delete();


            foreach ($menuItemsData as $itemData) {
                if ($itemData['parentId'] === null) {
                    $menuItem = ApiMenuItem::create([
                        'user_id' => $user_id,
                        'label' => $itemData['label'],
                        'url' => $itemData['url'],
                        'is_external' => $itemData['isExternal'],
                        'is_active' => $itemData['isActive'],
                        'order' => $itemData['order'],
                        'parent_id' => null,
                        'show_on_mobile' => $itemData['showOnMobile'],
                        'show_on_desktop' => $itemData['showOnDesktop'],
                    ]);
                    $parentIds[$itemData['id']] = $menuItem->id;
                }
            }

            foreach ($menuItemsData as $itemData) {
                if ($itemData['parentId'] !== null) {
                    if (!isset($parentIds[$itemData['parentId']])) {
                        throw new \Exception("Parent menu item with ID {$itemData['parentId']} does not exist.");
                    }

                    ApiMenuItem::create([
                        'user_id' => $user_id,
                        'label' => $itemData['label'],
                        'url' => $itemData['url'],
                        'is_external' => $itemData['isExternal'],
                        'is_active' => $itemData['isActive'],
                        'order' => $itemData['order'],
                        'parent_id' => $parentIds[$itemData['parentId']],
                        'show_on_mobile' => $itemData['showOnMobile'],
                        'show_on_desktop' => $itemData['showOnDesktop'],
                    ]);
                }
            }


            $settings = ApiMenuSetting::where('user_id', $user_id)->first();

            if (!$settings) {
                $settings = new ApiMenuSetting();
                $settings->user_id = $user_id;
            }

            $settings->menu_position = $settingsData['menuPosition'];
            $settings->menu_style = $settingsData['menuStyle'];
            $settings->mobile_menu_type = $settingsData['mobileMenuType'];
            $settings->is_sticky = $settingsData['isSticky'];
            $settings->is_transparent = $settingsData['isTransparent'];
            $settings->status = true;
            $settings->save();


            DB::commit();


            $updatedItems = ApiMenuItem::where('user_id', $user_id)->orderBy('order')->get();


            $formattedItems = $updatedItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'label' => $item->label,
                    'url' => $item->url,
                    'isExternal' => $item->is_external,
                    'isActive' => $item->is_active,
                    'order' => $item->order,
                    'parentId' => $item->parent_id,
                    'showOnMobile' => $item->show_on_mobile,
                    'showOnDesktop' => $item->show_on_desktop,
                ];
            });


            $formattedSettings = [
                'menuPosition' => $settings->menu_position,
                'menuStyle' => $settings->menu_style,
                'mobileMenuType' => $settings->mobile_menu_type,
                'isSticky' => $settings->is_sticky,
                'isTransparent' => $settings->is_transparent,
                'status' => $settings->status,
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'menuItems' => $formattedItems,
                    'settings' => $formattedSettings,
                ]
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update menu',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
