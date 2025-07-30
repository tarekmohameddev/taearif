<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Auth;
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

        return response()->json([
            'impersonation_token' => $plainTextToken,
            'token_type'          => 'Bearer',
        ]);
    }

    public function consume(Request $request, User $user)
    {
        $adminId = $request->query('adminId');

        session([
            'impersonator_admin_id'    => $adminId,
            'impersonated_user_id'     => $user->id,
            'impersonation_started_at' => now()->toIso8601String(),
        ]);

        Auth::guard('web')->login($user, false);

        $frontend = rtrim(env('FRONTEND_URL', url('/')), '/');

        $to = $request->query('redirect', $frontend);
        return redirect()->to($to);
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
