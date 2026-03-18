<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    private const OTP_MESSAGE_TEMPLATE = 'رمز التحقق الخاص بك هو: %s. صالح لمدة 5 دقائق.';

    /**
     * Send OTP to phone via WhatsApp (public). Never reveals whether the phone exists.
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $user = User::query()->where('phone', $phone)->first();

        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent.',
            ], 200);
        }

        $result = OtpVerification::createOrRefreshForUser($user);

        if (!$result['success']) {
            if (($result['error'] ?? '') === 'rate_limit_exceeded') {
                return response()->json([
                    'success' => false,
                    'error' => 'rate_limit_exceeded',
                    'message' => __('Too many OTP requests. Try again later.'),
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'OTP sent.',
            ], 200);
        }

        $plainOtp = $result['otp'];
        $message = sprintf(self::OTP_MESSAGE_TEMPLATE, $plainOtp);
        app(WhatsAppService::class)->sendMessage($phone, $message);

        if (app()->environment('local')) {
            \Illuminate\Support\Facades\Log::channel('single')->info('OTP (local only)', [
                'phone' => $phone,
                'otp' => $plainOtp,
                'expires_in_minutes' => 5,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent.',
        ], 200);
    }

    /**
     * Verify OTP and set phone_verified_at (auth:sanctum).
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $request->user();
        $code = $request->validated('otp');

        $result = OtpVerification::verifyForUser($user, $code);

        if ($result !== 'ok') {
            $messages = [
                'otp_invalid' => __('Invalid OTP.'),
                'otp_expired' => __('OTP has expired.'),
                'too_many_attempts' => __('Too many attempts. Request a new OTP.'),
                'otp_not_found' => __('No OTP found. Request one first.'),
            ];

            return response()->json([
                'success' => false,
                'error' => $result,
                'message' => $messages[$result] ?? __('Verification failed.'),
            ], 422);
        }

        $user->update(['phone_verified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Phone verified.',
            'data' => [
                'phone_verified_at' => $user->fresh()->phone_verified_at?->toIso8601String(),
            ],
        ], 200);
    }
}
