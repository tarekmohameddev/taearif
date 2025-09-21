<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PasswordResetLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Rules\Recaptcha;
use App\Services\EmailService;
use App\Services\WhatsAppService;


class ResetPasswordController extends Controller
{
    /**
     * Get user's preferred language or default to Arabic
     */
    private function getUserLanguage($user)
    {
        $defaultLanguage = $user->languages()->where('is_default', true)->first();
        return $defaultLanguage ? $defaultLanguage->code : 'ar';
    }

    /**
     * Send reset code (email or phone)
     */

    public function forgotPassword(Request $request)
    {
        // Validate only reCAPTCHA first
        $recaptchaValidator = Validator::make(
            $request->only('recaptcha_token'),
            ['recaptcha_token' => ['required', new \App\Rules\Recaptcha]]
        );

        if ($recaptchaValidator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'reCAPTCHA failed',
                'errors'  => $recaptchaValidator->errors()->toArray(),
            ], 422);
        }

        $request->validate([
            'identifier' => 'required',  // email or phone
            'method' => 'required|in:email,phone',

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

        // Get user's preferred language
        $userLanguage = $this->getUserLanguage($user);
        
        // Get frontend URL for reset link
        $frontendUrl = rtrim(env('FRONTEND_URL', url('/')), '/');
        $resetUrl = $frontendUrl . '/reset';

        // Send code
        if ($request->method === 'email') {
            $emailService = new EmailService();
            $emailSent = $emailService->sendPasswordResetCode(
                $user->email,
                $user->name ?? $user->username ?? 'User',
                $code,
                $userLanguage,
                null, // templateName - let service choose based on language
                $resetUrl
            );
            
            if (!$emailSent) {
                return response()->json([
                    'message' => 'Failed to send reset code. Please try again later.'
                ], 500);
            }
        } else {
            // Send via WhatsApp
            try {
                $whatsappService = new WhatsAppService();
                $whatsappSent = $whatsappService->sendPasswordResetCode(
                    $user->phone,
                    $code,
                    $user->name ?? $user->username ?? 'User',
                    $userLanguage,
                    $resetUrl
                );
                
                if (!$whatsappSent) {
                    return response()->json([
                        'message' => 'Failed to send reset code. Please try again later.'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'WhatsApp service not configured or failed to send message.'
                ], 500);
            }
        }
        return response()->json([
            'message' => "Reset code sent successfully (Attempt {$attemptNumber}/3)",
            'via' => $request->method,
            'attempts_used' => $attemptNumber,
            'attempts_remaining' => 3 - $attemptNumber,
            'code_for_testing' => $code // For testing purposes, remove in production
        ]);
    }

    /**
     * Verify reset code & reset password
     */
    public function verifyResetCode(Request $request)
    {

        // Validate only reCAPTCHA first
        $recaptchaValidator = Validator::make(
            $request->only('recaptcha_token'),
            ['recaptcha_token' => ['required', new \App\Rules\Recaptcha]]
        );

        if ($recaptchaValidator->fails()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'reCAPTCHA failed',
                'errors'  => $recaptchaValidator->errors()->toArray(),
            ], 422);
        }

        $request->validate([
            'code' => 'required|digits:6',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Find the reset log by code to identify the user
        $log = PasswordResetLog::where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>=', now())
            ->latest()
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Invalid or expired code'], 400);
        }

        // Get the user from the log
        $user = User::find($log->user_id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
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
