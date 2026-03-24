<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use App\Models\User;
use App\Models\PasswordResetLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Rules\Recaptcha;
use App\Services\EmailService;
use App\Services\WhatsAppService;

use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\VerifyResetCodeRequest;

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

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();

        $user = null;

        if ($request->method === 'phone') {
            // For phone method, we need to handle country code
            $countryCode = $request->country_code ?? '';
            $phoneNumber = $request->identifier;
            $fullPhoneNumber = $countryCode . $phoneNumber;

            Log::info('Phone reset attempt', [
                'phone_number' => $phoneNumber,
                'country_code' => $countryCode,
                'full_phone_number' => $fullPhoneNumber
            ]);

            // Search user by multiple phone number formats
            $user = User::where('email', $request->identifier)
                ->orWhere('phone', $phoneNumber)                    // Original number without country code
                ->orWhere('phone', $fullPhoneNumber)               // Full number with country code
                ->orWhere('phone', ltrim($fullPhoneNumber, '+'))   // Full number without + prefix
                ->first();

            // Store the full phone number for sending WhatsApp message
            $request->merge(['full_phone_number' => $fullPhoneNumber]);
        } else {
            // For email method, use original logic
            $user = User::where('email', $request->identifier)
                ->orWhere('phone', $request->identifier)
                ->first();
        }

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Load admin-configured max attempts/hour (fallback: 5).
        // Prefer dedicated password-reset limit, fallback to OTP limit for backward compatibility.
        $maxAttemptsPerHour = 5;
        try {
            $configuredLimit = (int) (BasicSetting::query()->value('password_reset_max_sends_per_hour') ?? 0);
            if ($configuredLimit <= 0) {
                $configuredLimit = (int) (BasicSetting::query()->value('otp_max_sends_per_hour') ?? 0);
            }
            if ($configuredLimit > 0) {
                $maxAttemptsPerHour = $configuredLimit;
            }
        } catch (\Throwable $e) {
            // Keep fallback when settings table is not ready/unavailable.
        }

        // Count attempts in the last hour for this user
        $attemptsLastHour = PasswordResetLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($attemptsLastHour >= $maxAttemptsPerHour) {
            return response()->json([
                'message' => 'You have reached the maximum reset password attempts for this hour',
                'attempts_used' => $attemptsLastHour,
                'attempts_remaining' => 0,
                'max_attempts_per_hour' => $maxAttemptsPerHour,
            ], 429);
        }

        // Otherwise this is a valid attempt in this 1-hour window
        $attemptNumber = $attemptsLastHour + 1;
        $emailBypass = $request->method === 'email' && $this->allowsPasswordResetEmailTestBypass();
        $code = $emailBypass
            ? (string) config('api.password_reset.email_test_bypass_code', '12345')
            : (string) rand(100000, 999999);

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
            if ($emailBypass) {
                Log::info('Password reset email skipped (test bypass)', [
                    'user_id' => $user->id,
                    'identifier' => $request->identifier,
                ]);
            } else {
                $emailService = new EmailService();
                $emailSent = $emailService->sendPasswordResetCode(
                    $user->email,
                    $user->name ?? $user->username ?? 'User',
                    $code,
                    $userLanguage,
                    null, // templateName - let service choose based on language
                    $resetUrl,
                    $user->id
                );

                if (!$emailSent) {
                    return response()->json([
                        'message' => 'Failed to send reset code. Please try again later.'
                    ], 500);
                }
            }
        } else {
            // Send via WhatsApp
            try {
                $whatsappService = new WhatsAppService();

                // Use the full phone number (with country code) for sending WhatsApp message
                $phoneForSending = $request->full_phone_number ?? $user->phone;

                $whatsappResult = $whatsappService->sendPasswordResetCode(
                    $phoneForSending,
                    $code,
                    $user->name ?? $user->username ?? 'User',
                    $userLanguage,
                    $resetUrl,
                    'password_reset', // templateName
                    $user->id
                );

                // If WhatsApp service is not configured, it returns the default message string
                // If it's configured, it returns true/false
                if (is_string($whatsappResult)) {
                    // WhatsApp service not configured, log the default message
                    Log::info('WhatsApp service not configured, using default message', [
                        'phone' => $phoneForSending,
                        'code' => $code,
                        'default_message' => $whatsappResult
                    ]);
                    // Continue with success response - the code was generated and stored
                } elseif (!$whatsappResult) {
                    return response()->json([
                        'message' => 'Failed to send reset code. Please try again later.'
                    ], 500);
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp service error', [
                    'phone' => $phoneForSending ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
                return response()->json([
                    'message' => 'WhatsApp service not configured or failed to send message.'
                ], 500);
            }
        }
        return response()->json([
            'message' => "Reset code sent successfully (Attempt {$attemptNumber}/{$maxAttemptsPerHour} this hour)",
            'via' => $request->method,
            'attempts_used' => $attemptNumber,
            'attempts_remaining' => max(0, $maxAttemptsPerHour - $attemptNumber),
            'max_attempts_per_hour' => $maxAttemptsPerHour,
            // 'code_for_testing' => $code // For testing purposes, remove in production
        ], 200);
    }

    /**
     * Verify reset code & reset password
     */
    public function verifyResetCode(VerifyResetCodeRequest $request)
    {
        $validated = $request->validated();

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

    private function allowsPasswordResetEmailTestBypass(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        if (!(bool) config('api.password_reset.email_test_bypass_enabled', false)) {
            return false;
        }

        return (string) config('api.password_reset.email_test_bypass_code', '') !== '';
    }
}
