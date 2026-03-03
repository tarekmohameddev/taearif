<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\Impersonation\StartImpersonationRequest;
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
    public function start(StartImpersonationRequest $request, User $user)
    {
        $admin = auth()->user();

        $plainTextToken = $user
            ->createToken('impersonated-by-'.$admin->id, ['*'])
            ->plainTextToken;

        return response()->json([
            'impersonation_token' => $plainTextToken,
            'token_type'          => 'Bearer',
        ]);
    }

    public function consume(Request $request)
    {
        $token = $request->input('token');
        if (!$token) {
            return response()->json(['message' => 'Token required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pat = PersonalAccessToken::findToken($token);
        if (!$pat) {
            return response()->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $pat->tokenable;
        if (!$user) {
            return response()->json(['message' => 'User not found'], Response::HTTP_UNAUTHORIZED);
        }

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();

        $pat->delete();

        return response()->json(['ok' => true]);
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
