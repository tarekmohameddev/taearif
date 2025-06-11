<?php

namespace App\Http\Controllers\Api\App;

use App\Models\Api\ApiApp;
use App\Models\AppRequest;
use Illuminate\Http\Request;
use App\Models\Api\ApiInstallation;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AppRequestController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $apps    = ApiApp::all();

        // Grab the user’s requests once, then key them by app_id for O(1) look-ups.
        $requests = AppRequest::where('user_id', $userId)
            ->get()
            ->keyBy('app_id');

        $apps = $apps->map(function ($app) use ($requests) {
            /** @var \App\Models\AppRequest|null $request */
            $request = $requests->get($app->id);

            return [
                'id'          => $app->id,
                'name'        => $app->name,
                'description' => $app->description,
                'price'       => number_format($app->price, 2),
                'type'        => $app->type,
                'rating'      => round($app->rating, 1),

                // ----- request-centric meta -----
                'requested'   => filled($request),
                // Accessor gives “Pending / Approved / Rejected”
                'status'      => optional($request)->status,
                'status_html' => optional($request)->status_label,
                'requested_at'=> optional($request)->created_at?->toIso8601String(),
                'phone_number'=> optional($request)->phone_number,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => ['apps' => $apps],
        ]);
    }

    /**
     * Install an app for the authenticated user.
     */
    public function install(Request $request)
    {
        $userId = Auth::id();
        if (! $userId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User not authenticated.',
            ], 401);
        }

        $request->validate([
            'app_id'               => 'required|exists:api_apps,id',
            'phone_number'         => 'nullable|string|max:20',
            'token'                => 'nullable|string|max:255',
        ]);

        Log::info('Creating/Updating AppRequest', [
            'user_id'      => $userId,
            'app_id'       => $request->app_id,
            'phone_number' => $request->phone_number,
        ]);

        $appRequest = AppRequest::updateOrCreate(
            ['user_id' => $userId, 'app_id' => $request->app_id],
            [
                'phone_number' => $request->phone_number,
                'token'        => $request->token,
                'status'       => 'pending',   // always start in “pending”
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Request submitted successfully.',
            'data'    => ['request' => $appRequest],
        ]);
    }

    /**
     * Uninstall an app for the authenticated user.
     */
    public function uninstall($appId)
    {
        $userId = Auth::id();

        $appRequest = AppRequest::where('user_id', $userId)
            ->where('app_id', $appId)
            ->firstOrFail();

        // Option A – keep a trail but mark as rejected:
        $appRequest->update(['status' => 'rejected']);

        // Option B – hard delete the row:
        // $appRequest->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'App request revoked successfully.',
        ]);
    }

}
