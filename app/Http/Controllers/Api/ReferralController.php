<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function validateCode(Request $request): JsonResponse
    {
        $code = (string) $request->query('code', '');

        if ($code === '') {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Missing code parameter.'
            ], 400);
        }

        $referrer = User::where('referral_code', $code)->first();

        if (! $referrer) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Invalid referral code.'
            ], 404);
        }

        return new JsonResponse([
            'valid' => true,
            'data'  => [
                'referral_code' => $referrer->referral_code,
                'user' => [
                    'id'       => $referrer->id,
                    'username' => $referrer->username,
                ],
            ],
        ]);
    }

    public function show(string $code): JsonResponse
    {
        $referrer = User::where('referral_code', $code)->first();

        if (! $referrer) {
            return new JsonResponse([
                'valid'   => false,
                'message' => 'Invalid referral code.'
            ], 404);
        }

        return new JsonResponse([
            'valid' => true,
            'data'  => [
                'referral_code' => $referrer->referral_code,
                'user' => [
                    'id'       => $referrer->id,
                    'username' => $referrer->username,
                ],
            ],
        ]);
    }
}


