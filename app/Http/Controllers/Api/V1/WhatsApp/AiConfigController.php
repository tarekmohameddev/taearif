<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppAiConfigService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppStatsService;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\WaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiConfigController extends BaseApiController
{
    public function __construct(
        private readonly WhatsAppAiConfigService $aiConfigService,
        private readonly WhatsAppStatsService $statsService
    ) {}

    public function show(int $numberId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $config = $this->aiConfigService->findForNumber($userId, $numberId);

        if (! $config) {
            return response()->json(['status' => 'error', 'code' => 'WA_AI_CONFIG_NOT_FOUND', 'message' => 'AI config for this number not found.'], 404);
        }

        return $this->ok(['data' => $config]);
    }

    public function update(Request $request, int $numberId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        if (! WaNumber::where('id', $numberId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }
        $validated = $request->validate([
            'enabled' => 'nullable|boolean',
            'business_hours_only' => 'nullable|boolean',
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            'scenarios' => 'nullable|array',
            'tone' => 'nullable|string|max:20',
            'language' => 'nullable|string|max:10',
            'custom_instructions' => 'nullable|string',
            'fallback_to_human' => 'nullable|boolean',
            'fallback_delay' => 'nullable|integer|min:0',
        ]);

        $config = $this->aiConfigService->createOrUpdate($userId, $numberId, $validated);

        return $this->ok(['data' => $config]);
    }

    public function toggle(int $numberId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        if (! WaNumber::where('id', $numberId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }
        $config = $this->aiConfigService->findForNumber($userId, $numberId);

        if (! $config) {
            return response()->json(['status' => 'error', 'code' => 'WA_AI_CONFIG_NOT_FOUND', 'message' => 'AI config for this number not found.'], 404);
        }

        $config = $this->aiConfigService->toggle($config);

        return $this->ok(['data' => $config]);
    }

    public function stats(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $stats = $this->statsService->aiStatsForUser($userId);

        return $this->ok(['data' => $stats]);
    }
}
