<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiThemeSettings;
use Illuminate\Support\Facades\Auth;

class ThemeSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $user = $request->user();
        $activeTheme = $user->userbasicsettings->first()?->theme ?? 'modern';
        $basicSetting = $user->userbasicsettings()->first();
        if (!$basicSetting) {
            $user->userbasicsettings()->create([
                'theme' => $activeTheme
            ]);
        } elseif (!$basicSetting->theme) {
            $basicSetting->theme = $activeTheme;
            $basicSetting->save();
        }

        if (ApiThemeSettings::count() === 0) {
            ApiThemeSettings::insert([
                [
                    'theme_id' => 'home13',
                    'name' => 'Real Estate Theme',
                    'description' => 'Designed for real estate listings and agencies.',
                    'thumbnail' => 'themes/home13/thumb.png',
                    'category' => 'real_estate',
                    'active' => true,
                    'popular' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'theme_id' => 'modern',
                    'name' => 'Modern Theme',
                    'description' => 'Clean and minimal design for modern portfolios.',
                    'thumbnail' => 'themes/modern/thumb.png',
                    'category' => 'modern',
                    'active' => true,
                    'popular' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
        }

        $themes = ApiThemeSettings::all();

        $categories = [
            ['id' => 'all', 'name' => 'جميع السمات'],
        ];

        return response()->json([
            'activeTheme' => $activeTheme,
            'themes' => $themes->map(function ($theme) use ($activeTheme) {
                return [
                    'id' => $theme->theme_id,
                    'name' => $theme->name,
                    'description' => $theme->description,
                    'thumbnail' => asset($theme->thumbnail),
                    'category' => $theme->category,
                    'active' => $theme->theme_id === $activeTheme,
                    'popular' => $theme->popular,
                ];
            }),
            'categories' => $categories,
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

    public function setActiveTheme(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'theme_id' => 'required|exists:api_themes_settings,theme_id',
        ]);

        $themeId = $request->theme_id;
        ApiThemeSettings::query()->update(['active' => false]);
        ApiThemeSettings::where('theme_id', $themeId)->update(['active' => true]);
        $basicSetting = BasicSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['theme' => $themeId]
        );

        if ($basicSetting->theme !== $themeId) {
            $basicSetting->theme = $themeId;
            $basicSetting->save();
        }

        $theme = ApiThemeSettings::where('theme_id', $themeId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Theme activated successfully',
            'data' => [
                'id' => $theme->theme_id,
                'name' => $theme->name,
                'description' => $theme->description,
                'thumbnail' => asset($theme->thumbnail),
                'category' => $theme->category,
                'active' => true,
            ],
        ]);
    }

}
