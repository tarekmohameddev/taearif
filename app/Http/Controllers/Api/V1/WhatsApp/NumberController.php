<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppNumberService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\StoreWaNumberRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateWaNumberRequest;
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

    public function store(StoreWaNumberRequest $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validated();
        $waNumber = $this->numberService->create($userId, $validated);

        return $this->created($waNumber);
    }

    public function update(UpdateWaNumberRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $waNumber = $this->numberService->findForUser($userId, $id);

        if (! $waNumber) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }

        $validated = $request->validated();
        $waNumber = $this->numberService->update($waNumber, $validated);

        return $this->ok(['data' => $waNumber]);
    }
}
