<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

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

    public function Callback(Request $request)
    {
        try {
            // Validate the code from Google
            $request->validate([
                'code' => 'required|string',
            ]);

            // Get Google user using the authorization code
            $googleUser = Socialite::driver('google')->stateless()->user();
            // Find or create user
            $user = User::where('google_id', $googleUser->id)->orWhere('email', $googleUser->email)->first();

            if ($user) {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                    // $user->refresh();
                }
                \Log::info('User: ' . $user->toJson());
                // Check email verification and status (matching LoginController)
                if ($user->email_verified == 0) {
                    return response()->json(['error' => __('Your Email is not Verified!')], 403);
                }
                if ($user->status == 0) {
                    return response()->json(['error' => __('Your account has been banned')], 403);
                }
            } else {
                // Create a new user
                $user = User::create([
                    'first_name' => $googleUser->name,
                    'last_name' => $googleUser->family_name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => Hash::make(uniqid()), // Random password
                    'email_verified' => 1, // Google accounts are typically verified
                    'status' => 1, // Adjust based on your logic
                ]);
            }

            // Log in the user using the 'web' guard
            Auth::guard('web')->login($user, true);

            // Create a Sanctum token
            $token = $user->createToken('auth_token')->plainTextToken;
            \Log::info('Google User: ' . json_encode($googleUser));
            \Log::info('User: ' . $user);

            // Return token and user data
            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $googleUser->name,
                    'email' => $user->email,
                    'google_id' => $googleUser->id,
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::info('Google Auth Error: ' . $e->getMessage());
            return response()->json(['error' => __('Unable to login with Google. Please try again.')], 500);
        }
    }
}
