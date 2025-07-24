<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Rules\Recaptcha;


class ResetPasswordController extends Controller
{
    /**
     * Send reset code (email or phone)
     */

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required',  // email or phone
            'method' => 'required|in:email,phone',
            'recaptcha_token' => ['required', new Recaptcha] //Verify Google reCAPTCHA token

        ]);

        $user = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Check if already blocked blocked_until
        $latestLog = PasswordResetLog::where('user_id', $user->id)->latest()->first();
        if ($latestLog && $latestLog->blocked && now()->lt($latestLog->blocked_until)) {
            return response()->json([
                'message' => 'Too many attempts. Try again later',
            ], 429);
        }

        // Count attempts in the last 24h
        $attemptsLast24h = PasswordResetLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();

        // If already 3 attempts → block without adding a new row
        if ($attemptsLast24h >= 3) {
            // Block for 24 hours
            $blockedUntil = $latestLog && $latestLog->blocked_until ? $latestLog->blocked_until : now()->addDay();

            if ($latestLog && !$latestLog->blocked) {
                $latestLog->update([
                    'blocked' => true,
                    'blocked_until' => $blockedUntil
                ]);
            }

            return response()->json([
                'message' => 'You have reached the maximum 3 attempts'
            ], 429);
        }

        // Otherwise this is a valid attempt (1st, 2nd or 3rd)
        $attemptNumber = $attemptsLast24h + 1;
        $code = rand(100000, 999999);

        PasswordResetLog::create([
            'user_id' => $user->id,
            'method' => $request->method,
            'code' => $code,
            'attempts' => $attemptNumber,
            'blocked' => false,
            'blocked_until' => null,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Send code
        if ($request->method === 'email') {
            // Mail::raw("Your password reset code is: {$code}", function ($message) use ($user) {
            //     $message->to($user->email)->subject('Your Password Reset Code');
            // });
        } else {
            // Placeholder for SMS/WhatsApp
        }
        return response()->json([
            'message' => "Reset code sent successfully (Attempt {$attemptNumber}/3)",
            'via' => $request->method,
            'attempts_used' => $attemptNumber,
            'attempts_remaining' => 3 - $attemptNumber
        ]);
    }

    /**
     * Verify reset code & reset password
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'identifier' => 'required', // email or phone
            'code' => 'required|digits:6',
            'new_password' => 'required|min:8|confirmed',
            'recaptcha_token' => ['required', new Recaptcha] //Verify Google reCAPTCHA token
        ]);

        $user = User::where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $log = PasswordResetLog::where('user_id', $user->id)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        // Reset password
        $user->update(['password' => Hash::make($request->new_password)]);

        // Mark code as used
        $log->update(['used' => true]);

        // Delete all old attempts for this user (reset attempt history)
        PasswordResetLog::where('user_id', $user->id)->delete();

        return response()->json([
            'message' => 'Password reset successful'
        ]);
    }
}
