<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppAiConfigService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppStatsService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\UpdateAiConfigRequest;
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

    public function update(UpdateAiConfigRequest $request, int $numberId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        if (! WaNumber::where('id', $numberId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }
        $validated = $request->validated();
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

    public function stats(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        $filters = [
            'wa_number_id' => $request->input('wa_number_id') ? (int) $request->input('wa_number_id') : null,
            'period'       => $request->input('period', 'this_month'),
        ];

        if (! is_null($filters['wa_number_id'])) {
            if (! WaNumber::where('id', $filters['wa_number_id'])->where('user_id', $userId)->exists()) {
                return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
            }
        }

        $stats = $this->statsService->aiStatsForUser($userId, $filters);

        return $this->ok(['data' => $stats]);
    }
}
