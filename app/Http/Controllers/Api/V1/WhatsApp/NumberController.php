<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppNumberService;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NumberController extends BaseApiController
{
    public function __construct(private readonly WhatsAppNumberService $numberService) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->numberService->listForUser($userId, [
            'status' => $request->input('status'),
        ], $perPage);

        return response()->json([
            'status' => true,
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $waNumber = $this->numberService->findForUser($userId, $id);

        if (! $waNumber) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }

        return $this->ok(['data' => $waNumber]);
    }

    public function store(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validate([
            'provider' => 'required|in:meta,evolution',
            'phone_number' => 'required|string|max:20',
            'phone_number_id' => 'nullable|string|max:191',
            'provider_account_id' => 'nullable|string|max:191',
            'name' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,pending',
            'quota_limit' => 'nullable|integer|min:0',
            'meta' => 'nullable|array',
        ]);

        $waNumber = $this->numberService->create($userId, $validated);

        return $this->created($waNumber);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $waNumber = $this->numberService->findForUser($userId, $id);

        if (! $waNumber) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,pending',
            'quota_limit' => 'nullable|integer|min:0',
            'phone_number_id' => 'nullable|string|max:191',
            'provider_account_id' => 'nullable|string|max:191',
            'meta' => 'nullable|array',
        ]);

        $waNumber = $this->numberService->update($waNumber, $validated);

        return $this->ok(['data' => $waNumber]);
    }
}
