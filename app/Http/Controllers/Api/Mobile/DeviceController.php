<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Mobile\RegisterDeviceRequest;
use App\Models\MobileDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends ApiController
{
    public function register(RegisterDeviceRequest $request): JsonResponse
    {
        $user = $request->user();

        MobileDeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => (int) $user->id,
                'platform' => $request->platform,
                'last_used_at' => now(),
            ]
        );

        return $this->success([
            'message' => 'Device registered successfully',
        ]);
    }

    public function unregister(Request $request, string $token): JsonResponse
    {
        $user = $request->user();

        MobileDeviceToken::where('token', $token)
            ->where('user_id', (int) $user->id)
            ->delete();

        return $this->success([
            'message' => 'Device unregistered',
        ]);
    }
}
