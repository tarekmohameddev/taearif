<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Session;

class GoogleAuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest:web'); // Use 'web' guard to match LoginController
        $this->middleware('setlang'); // Reuse setlang middleware from LoginController
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('google_id', $googleUser->id)->orWhere('email', $googleUser->email)->first();

            if ($user) {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->id]);
                }
                // Check email verification and status
                if ($user->email_verified == 0) {
                    return redirect()->route('user.login')->with('err', __('Your Email is not Verified!'));
                }
                if ($user->status == 0) {
                    return redirect()->route('user.login')->with('err', __('Your account has been banned'));
                }
            } else {
                // Create a new user
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'password' => Hash::make(uniqid()), // Random password
                    'email_verified' => 1, // Google accounts are typically verified
                    'status' => 1, // Set default status (adjust based on your logic)
                ]);
            }

            // Log in using the 'web' guard to match LoginController
            Auth::guard('web')->login($user, true);

            // Handle redirect based on session link (mimicking LoginController)
            if (Session::has('link')) {
                $redirectUrl = Session::get('link');
                Session::forget('link');
                return redirect($redirectUrl);
            }

            return redirect()->route('user-dashboard');
        } catch (\Exception $e) {
            return redirect()->route('user.login')->with('err', __('Unable to login with Google. Please try again.'));
        }
    }
}
