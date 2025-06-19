<?php

namespace App\Http\Controllers\Api\App;

use App\Models\Api\ApiApp;
use App\Models\AppRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use App\Models\Api\ApiMenuItem;
use App\Models\Api\ApiInstallation;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\InstallationService;

class ApiInstallationController extends Controller
{
    /**
     * Display a listing of the installed apps for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function index()
    {
        $userId = auth()->id();
        $apps = ApiApp::all();

        $installations = ApiInstallation::with('settings')
            ->where('user_id', $userId)
            ->whereIn('app_id', $apps->pluck('id'))
            ->get()
            ->keyBy('app_id');

        $apps = $apps->map(function ($app) use ($installations) {
            $installation = $installations->get($app->id);

            return [
                'id' => $app->id,
                'name' => $app->name,
                'img' => $app->img,
                'description' => $app->description,
                'price' => number_format($app->price, 2),
                'type' => $app->type,
                'rating' => round($app->rating, 1),
                'billing_type' => $app->billing_type,
                'trial_days' => $app->trial_days ?? 0,
                'installed' => $installation->installed ?? false,
                'trial_ends_at' => $installation->trial_ends_at ?? null,
                'current_period_end' => $installation->current_period_end ?? null,
                'activated_at' => $installation->activated_at ?? null,
                'status' => $installation->status ?? 'pending',
                'settings' => $installation->settings ?? null,
                'installed_at' => $installation->installed_at ?? null,
                'uninstalled_at' => $installation->uninstalled_at ?? null,
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
    // public function install(Request $request,InstallAppRequest $req, InstallationService $svc)
    // {
    //     $userId = Auth::id();
    //     if (!$userId) {
    //         return response()->json([
    //             'status'  => 'error',
    //             'message' => 'User not authenticated.',
    //         ], 401);
    //     }

    //     $request->validate([
    //         'app_id'                => 'required|exists:api_apps,id',
    //         'settings'              => 'nullable|array',
    //         'settings.phone_number' => 'nullable|string|max:20',
    //         'settings.token'        => 'nullable|string|max:255',
    //     ]);

    //     /** @var \App\Models\ApiApp $app */
    //     $app = ApiApp::findOrFail($request->app_id);

    //     // ── Billing path ──────────────────────────────────────────────
    //     $isTrial = $app->billing_type === 'subscription' && $app->trial_days > 0;
    //     $isFree  = $app->billing_type === 'free';
    //     $now     = CarbonImmutable::now();

    //     $trialEndsAt       = $isTrial ? $now->addDays($app->trial_days) : null;
    //     $currentPeriodEnd  = $isTrial ? $trialEndsAt : null;   // real value set by Stripe later
    //     $status            = $isTrial ? 'trialing' : 'installed';

    //     // ── Persist installation ─────────────────────────────────────
    //     $installation = ApiInstallation::updateOrCreate(
    //         ['user_id' => $userId, 'app_id' => $app->id],
    //         [
    //             // NEW
    //             'status'             => $status,
    //             'activated_at'       => $now,
    //             'trial_ends_at'      => $trialEndsAt,
    //             'current_period_end' => $currentPeriodEnd,

    //             // LEGACY
    //             'installed'          => 1 ,
    //             'installed_at'       => $now,
    //             'uninstalled_at'     => null,
    //         ]
    //     );

    //     // settings relationship
    //     $installation->settings()->updateOrCreate(
    //         ['installation_id' => $installation->id],
    //         ['settings' => $request->input('settings', [])]
    //     );

    //     // user request record (unchanged)
    //     $settings = $request->input('settings', []);
    //     AppRequest::updateOrCreate(
    //         ['user_id' => $userId, 'app_id' => $app->id],
    //         [
    //             'phone_number' => $settings['phone_number'] ?? null,
    //             'token'        => $settings['token']        ?? null,
    //             'status'       => 'approved',
    //         ]
    //     );

    //     if ($app->name === 'واتس اب') {
    //         $menuItem = \App\Models\Api\ApiMenuItem::firstOrCreate(
    //             ['user_id' => $userId, 'url' => '/whatsapp-ai'],
    //             [
    //                 'label' => 'واتس اب',
    //                 'is_external' => false,
    //                 'is_active' => true,
    //                 'order' => 8,
    //                 'parent_id' => null,
    //                 'show_on_mobile' => true,
    //                 'show_on_desktop' => true,
    //             ]
    //         );

    //         // If it existed but was inactive, activate it
    //         if (!$menuItem->is_active) {
    //             $menuItem->is_active = true;
    //             $menuItem->save();
    //         }
    //     }

    //     Log::info("App installed: {$app->name} (ID: {$app->id}) for user ID: {$userId}");

    //     // ── Response ─────────────────────────────────────────────────
    //     return response()->json([
    //         'status'  => 'success',
    //         'message' => 'App installed successfully.',
    //         'data'    => ['installation' => $installation],
    //     ]);
    // }
    public function install(Request $req, InstallationService $svc)
    {
        $req->validate(['app_id' => 'required|exists:api_apps,id', 'settings' => 'array']);
        $user   = $req->user();
        $app    = ApiApp::findOrFail($req->app_id);
        $result = $svc->install($user, $app, $req->input('settings', []));

        if ($app->name === 'واتس اب') {
            $menuItem = \App\Models\Api\ApiMenuItem::firstOrCreate(
                ['user_id' => $user->id, 'url' => '/whatsapp-ai'],
                [
                    'label' => 'واتس اب',
                    'is_external' => false,
                    'is_active' => true,
                    'order' => 8,
                    'parent_id' => null,
                    'show_on_mobile' => true,
                    'show_on_desktop' => true,
                ]
            );

            // If it existed but was inactive, activate it
            if (!$menuItem->is_active) {
                $menuItem->update(['is_active' => true]);
                $menuItem->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'data'   => [
                'installation' => $result['installation'],
                'app'          => [
                    'id'           => $app->id,
                    'billing_type' => $app->billing_type,
                    'trial_days'   => $app->trial_days,
                    'price'        => $app->price,
                    'name'         => $app->name,
                ],
                'payment_url'  => $result['payment_url'],
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

        \App\Models\Api\ApiMenuItem::where('user_id', $userId)
        ->where('url', '/whatsapp-ai')
        ->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'App uninstalled successfully.',
        ]);
    }

}
