<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Notifications\DevicePushTokenService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\StorePushTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationDeviceController extends Controller
{
    public function store(StorePushTokenRequest $request, DevicePushTokenService $service): JsonResponse
    {
        $user = $request->user();
        $tenantId = method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
        $token = $service->upsert((int) $user->id, $tenantId, $request->validated());

        unset($token['token']);
        return response()->json(['data' => $token], 201);
    }

    public function destroy(Request $request, DevicePushTokenService $service): JsonResponse
    {
        $data = $request->validate([
            'device_id' => ['nullable', 'required_without:token', 'string', 'max:191'],
            'token' => ['nullable', 'required_without:device_id', 'string', 'max:4096'],
        ]);
        $count = $service->deactivate(
            (int) $request->user()->id,
            $data['device_id'] ?? null,
            $data['token'] ?? null
        );

        return response()->json(['deactivated' => $count]);
    }
}
