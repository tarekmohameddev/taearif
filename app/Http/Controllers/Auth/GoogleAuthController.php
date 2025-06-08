<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\TempTokenService;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;


class GoogleAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('setlang'); // Keep setlang middleware for consistency
    }

    public function getGoogleAuthUrl()
    {
        $url = Socialite::driver('google')->stateless()->redirect()->getTargetUrl();
        return response()->json(['url' => $url,], 200);
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Find user by email or google_id
            $user = User::where('google_id', $googleUser->id)
                        ->orWhere('email', $googleUser->email)
                        ->first();
            // log::info($user);
            // $user = null;
            // log::info($user);
            if (!$user) {
                $payload = [
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'expires_at' => now()->addMinutes(10)->timestamp,
                ];

                $tempToken = TempTokenService::generate($payload);

                return redirect()->away("https://app.taearif.com/oauth/social/extra-info?temp_token={$tempToken}");
            }

            if ($user->email === $googleUser->email && !$user->google_id) {
                // (!$user->google_id || $user->google_id !== $googleUser->id)
                return redirect()->away('https://app.taearif.com/oauth/login?error=not_registered_with_google');
            }

            if ($user->status == 0) {
                return redirect()->away('https://app.taearif.com/oauth/login?error=account_banned');
            }

            Auth::login($user);
            $token = $user->createToken('auth_token')->plainTextToken;

            return redirect()->away("https://app.taearif.com/oauth/token/success?token={$token}");

        } catch (\Exception $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            return redirect()->away("https://app.taearif.com/oauth/login?error=google_auth_failed");
        }
    }
}
