<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SendOtpRequest;
use App\Http\Requests\Api\Auth\VerifyOtpRequest;
use App\Models\OtpVerification;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class OtpController extends Controller
{
    /**
     * Send OTP to phone via WhatsApp (registration or logged-in phone verification).
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $phone = $request->validated('phone');
        $authUser = auth('sanctum')->user();
        $phoneOwner = User::query()->where('phone', $phone)->first();

        if ($phoneOwner && (!$authUser || $authUser->id !== $phoneOwner->id)) {
            return $this->phoneAlreadyRegisteredResponse();
        }

        if ($authUser) {
            $result = OtpVerification::createOrRefreshForUser($authUser);
        } else {
            $result = OtpVerification::createOrRefreshForPhone($phone);
        }

        if (!($result['success'] ?? false)) {
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

        return $this->deliverOtp($phone, $result['otp']);
    }

    /**
     * Verify OTP and set phone_verified_at (auth) or issue verified_token (pre-registration).
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $code = $request->validated('otp');

        // When this endpoint is public, we may not have `auth:sanctum` middleware.
        // Therefore we resolve the user via the guard directly.
        $user = auth('sanctum')->user();

        if ($user) {
            $result = OtpVerification::verifyForUser($user, $code);
        } else {
            $phone = $request->validated('phone');
            if (empty($phone)) {
                return response()->json([
                    'success' => false,
                    'message' => 'phone is required when no auth token is provided.',
                ], 422);
            }

            if (User::query()->where('phone', $phone)->exists()) {
                return $this->phoneAlreadyRegisteredResponse();
            }

            $result = OtpVerification::verifyForPhone($phone, $code);
        }

        if ($user) {
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

        // pre-registration path (phone-based)
        $resultCode = $result['result'] ?? '';
        if ($resultCode !== 'ok') {
            $messages = [
                'otp_invalid' => __('Invalid OTP.'),
                'otp_expired' => __('OTP has expired.'),
                'too_many_attempts' => __('Too many attempts. Request a new OTP.'),
                'otp_not_found' => __('No OTP found. Request one first.'),
            ];

            return response()->json([
                'success' => false,
                'error' => $resultCode,
                'message' => $messages[$resultCode] ?? __('Verification failed.'),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Phone verified.',
            'verified_token' => $result['verified_token'],
        ], 200);
    }

    private function deliverOtp(string $phone, string $plainOtp): JsonResponse
    {
        if (OtpVerification::isTestBypassActive($phone)) {
            Log::warning('OTP test bypass delivery skipped', [
                'phone_masked' => strlen($phone) < 4 ? '****' : '****' . substr($phone, -4),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'OTP sent.',
            ], 200);
        }

        $sent = app(WhatsAppService::class)->sendRegistrationOtp($phone, $plainOtp);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'error' => 'delivery_failed',
                'message' => __('Unable to send OTP. Please try again later.'),
            ], 503);
        }

        if (app()->environment('local')) {
            Log::channel('single')->info('OTP (local only)', [
                'phone' => $phone,
                'otp' => $plainOtp,
                'expires_in_minutes' => OtpVerification::OTP_EXPIRY_MINUTES,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent.',
        ], 200);
    }

    private function phoneAlreadyRegisteredResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => 'phone_already_registered',
            'message' => __('This phone number is already registered. Please log in.'),
        ], 409);
    }
}
