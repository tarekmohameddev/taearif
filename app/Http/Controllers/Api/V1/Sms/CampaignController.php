<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Sms\Services\SmsCampaignService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Sms\StoreCampaignRequest;
use App\Http\Requests\Api\V1\Sms\UpdateCampaignRequest;
use App\Http\Requests\Api\V1\Sms\ResumeCampaignRequest;
use App\Http\Requests\Api\V1\Sms\SendCampaignRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CampaignController extends BaseApiController
{
    public function __construct(private readonly SmsCampaignService $campaignService) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);

        $items = $this->campaignService->listForUser($userId, [
            'status' => $request->input('status'),
        ], $perPage);

        return $this->ok($items);
    }

    public function show(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $campaign = $this->campaignService->findForUser($userId, $id);
        if (!$campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        return $this->ok($campaign);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $user = auth()->user();
        $userId = (int) $user->tenantOwnerId();
        $createdByUserId = (int) $user->getKey();
        $validated = $request->validated();
        $campaign = $this->campaignService->create($userId, $createdByUserId, $validated);

        return $this->created($campaign);
    }

    public function update(UpdateCampaignRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $campaign = $this->campaignService->findForUser($userId, $id);
        if (!$campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        $validated = $request->validated();
        try {
            $updated = $this->campaignService->update($campaign, $validated);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        }

        return $this->ok($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $campaign = $this->campaignService->findForUser($userId, $id);
        if (!$campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        try {
            $this->campaignService->delete($campaign);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        }

        return $this->ok(['deleted' => true]);
    }

    public function send(SendCampaignRequest $request, int $id): JsonResponse
    {
        $key = trim((string) request()->header('Idempotency-Key', ''));
        $validated = $request->validated();
        $userId = (int) auth()->user()->tenantOwnerId();

        try {
            $data = $this->campaignService->sendCampaign(
                $userId,
                $id,
                $key,
                $validated['customer_ids'] ?? [],
                $validated['manual_phones'] ?? []
            );
        } catch (ModelNotFoundException) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        } catch (IdempotencyConflictException $e) {
            return response()->json(['status' => false, 'code' => strtoupper((string) $e->reason), 'message' => $e->getMessage()], 409);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['status' => false, 'code' => 'INSUFFICIENT_CREDITS', 'message' => $e->getMessage()], 400);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'data' => $data], 202);
    }

    public function pause(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        try {
            $data = $this->campaignService->pause($userId, $id);
        } catch (ModelNotFoundException) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'data' => $data], 200);
    }

    public function resume(ResumeCampaignRequest $request, int $id): JsonResponse
    {
        $key = trim((string) request()->header('Idempotency-Key', ''));
        $validated = $request->validated();
        $userId = (int) auth()->user()->tenantOwnerId();

        try {
            $data = $this->campaignService->resume(
                $userId,
                $id,
                $key,
                $validated['mode'],
                $validated['customer_ids'] ?? [],
                $validated['manual_phones'] ?? []
            );
        } catch (ModelNotFoundException) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        } catch (IdempotencyConflictException $e) {
            return response()->json(['status' => false, 'code' => strtoupper((string) $e->reason), 'message' => $e->getMessage()], 409);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['status' => false, 'code' => 'INSUFFICIENT_CREDITS', 'message' => $e->getMessage()], 400);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => true, 'data' => $data], 202);
    }
}
