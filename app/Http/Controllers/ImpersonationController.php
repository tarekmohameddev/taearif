<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

class ImpersonationController extends Controller
{
    /**
     * Issue an impersonation token for the given user.
     *
     * Route: POST /api/impersonate/{user}
     * Guard: auth:sanctum  (admin’s Bearer token must be present)
     *
     * Response:
     * {
     *     "impersonation_token": "63|d7M4xZ...Cf1",
     *     "token_type": "Bearer"
     * }
     */
    public function start(Request $request, User $user)
    {
        $admin = $request->user();

        $plainTextToken = $user
            ->createToken('impersonated-by-'.$admin->id, ['*'])
            ->plainTextToken;

        Log::info("Admin {$admin->id} IMPERSONATE-START user {$user->id}");

        return response()->json([
            'impersonation_token' => $plainTextToken,
            'token_type'          => 'Bearer',
        ]);
    }

    /**
     * Revoke every impersonation token this admin issued for that user.
     *
     * Route: POST /api/impersonate/{user}/revoke
     * Guard: auth:sanctum  (admin’s Bearer token again)
     */
    public function stop(Request $request, User $user)
    {
        $admin = $request->user();

        $deleted = $user->tokens()
            ->where('name', 'like', 'impersonated-by-'.$admin->id.'%')
            ->delete();

        Log::info("Admin {$admin->id} IMPERSONATE-STOP  user {$user->id} — {$deleted} token(s) revoked");


        return response()->json([
            'revoked_tokens' => $deleted,
            'message'        => 'Impersonation ended — use your admin token again.',
        ]);
    }

    /**
     * (Optional helper) Revoke one specific token if the client sends it back.
     * POST /api/impersonate/revoke-one   body: { "token": "63|abc..." }
     */
    public function revokeOne(Request $request)
    {
        $admin = $request->user();
        $plain = $request->input('token');
        $id    = explode('|', $plain)[0] ?? null;

        $success = PersonalAccessToken::query()
            ->whereKey($id)
            ->where('name', 'like', 'impersonated-by-'.$admin->id.'%')
            ->delete();

        return response()->json(['revoked' => (bool) $success]);
    }
}
