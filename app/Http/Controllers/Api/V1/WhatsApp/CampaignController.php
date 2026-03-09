<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\WhatsApp\Services\WaCampaignService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\ResumeWaCampaignRequest;
use App\Http\Requests\Api\V1\WhatsApp\SendWaCampaignRequest;
use App\Http\Requests\Api\V1\WhatsApp\StoreWaCampaignRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateWaCampaignRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CampaignController extends BaseApiController
{
    public function __construct(private readonly WaCampaignService $campaignService) {}

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
        if (! $campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        return $this->ok($campaign);
    }

    public function store(StoreWaCampaignRequest $request): JsonResponse
    {
        $user = auth()->user();
        $userId = (int) $user->tenantOwnerId();
        $createdByUserId = (int) $user->getKey();
        $validated = $request->validated();
        try {
            $campaign = $this->campaignService->create($userId, $createdByUserId, $validated);
        } catch (InvalidArgumentException $e) {
            $code = $e->getMessage();
            $status = $code === 'WA_NUMBER_NOT_FOUND' ? 404 : 422;
            return response()->json(['status' => false, 'code' => $code, 'message' => $e->getMessage()], $status);
        }

        return $this->created($campaign);
    }

    public function update(UpdateWaCampaignRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $campaign = $this->campaignService->findForUser($userId, $id);
        if (! $campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        $validated = $request->validated();
        try {
            $updated = $this->campaignService->update($campaign, $validated);
        } catch (InvalidArgumentException $e) {
            $code = $e->getMessage();
            $status = $code === 'WA_NUMBER_NOT_FOUND' ? 404 : 422;
            return response()->json(['status' => false, 'code' => $code, 'message' => $e->getMessage()], $status);
        }

        return $this->ok($updated);
    }

    public function destroy(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $campaign = $this->campaignService->findForUser($userId, $id);
        if (! $campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        try {
            $this->campaignService->delete($campaign);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        }

        return $this->ok(['deleted' => true]);
    }

    public function send(SendWaCampaignRequest $request, int $id): JsonResponse
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
            $code = $e->getMessage();
            $status = $code === 'WA_NUMBER_NOT_FOUND' ? 404 : 422;
            return response()->json(['status' => false, 'code' => $code, 'message' => $e->getMessage()], $status);
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

    public function resume(ResumeWaCampaignRequest $request, int $id): JsonResponse
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
            $code = $e->getMessage();
            $status = $code === 'WA_NUMBER_NOT_FOUND' ? 404 : 422;
            return response()->json(['status' => false, 'code' => $code, 'message' => $e->getMessage()], $status);
        }

        return response()->json(['status' => true, 'data' => $data], 202);
    }
}
