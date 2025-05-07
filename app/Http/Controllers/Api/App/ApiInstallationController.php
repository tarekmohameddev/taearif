<?php

namespace App\Http\Controllers\Api\App;

use App\Models\Api\ApiApp;
use Illuminate\Http\Request;
use App\Models\Api\ApiInstallation;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ApiInstallationController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $apps = ApiApp::all();
        $installations = ApiInstallation::with('settings')
            ->where('user_id', $userId)
            ->whereIn('app_id', $apps->pluck('id'))
            ->get()
            ->keyBy('app_id');
        // Map apps with install info
        $apps = $apps->map(function ($app) use ($installations) {
            $installation = $installations->get($app->id);
            $isInstalled = $installation && $installation->status === 'installed';
            return [
                'id' => $app->id,
                'name' => $app->name,
                'description' => $app->description,
                'price' => $app->price,
                'type' => $app->type,
                'rating' => $app->rating,
                'installed' => $isInstalled,
                'status' => $isInstalled ? $installation->status : null,
                'settings' => $isInstalled ? optional($installation->settings)->settings : null,
                'installed_at' => $isInstalled ? optional($installation->installed_at)->toIso8601String() : null,
            ];

        });
        return response()->json([
            'status' => 'success',
            'data' => [
                'apps' => $apps,
            ],
        ]);
    }

    /**
     * Install an app for the authenticated user.
     */
    public function install(Request $request)
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.',
            ], 401);
        }

        \Log::info('Installing app', [
            'user_id' => $userId,
            'app_id' => $request->app_id,
            'settings' => $request->input('settings', []),

        ]);

        $request->validate([
            'app_id' => 'required|exists:api_apps,id',
            'settings' => 'nullable|array',
        ]);


        $installation = ApiInstallation::updateOrCreate(
            ['user_id' => $userId, 'app_id' => $request->app_id],
            [
                'status' => 'installed',
                'installed_at' => now(),
                'uninstalled_at' => null,
            ]
        );
        $installation->settings()->updateOrCreate(
            ['installation_id' => $installation->id],
            [
                'settings' => $request->input('settings', []),
            ]
        );
        return response()->json([
            'status' => 'success',
            'message' => 'App installed successfully.',
            'data' => [
                'installation' => $installation,
            ],
        ]);
    }

    /**
     * Uninstall an app for the authenticated user.
     */
    public function uninstall($appId)
    {
        $userId = Auth::id();

        $installation = ApiInstallation::where('user_id', $userId)
            ->where('app_id', $appId)
            ->firstOrFail();
        $installation->update([
            'status' => 'uninstalled',
            'installed' => false,
            'uninstalled_at' => now(),
        ]);
        $installation->settings()->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'App uninstalled successfully.',
        ]);
    }
}
