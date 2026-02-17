<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Sms\Services\SmsCampaignService;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function store(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'message' => 'required|string',
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('sms_templates', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'status' => 'nullable|in:draft,scheduled',
            'scheduled_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ]);

        $campaign = $this->campaignService->create($userId, $validated);

        return $this->created($campaign);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $campaign = $this->campaignService->findForUser($userId, $id);
        if (!$campaign) {
            return response()->json(['status' => false, 'code' => 'CAMPAIGN_NOT_FOUND', 'message' => 'Campaign not found.'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'message' => 'sometimes|string',
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('sms_templates', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'status' => 'sometimes|in:draft,scheduled',
            'scheduled_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ]);

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

    public function send(Request $request, int $id): JsonResponse
    {
        $key = trim((string) $request->header('Idempotency-Key', ''));
        if ($key === '') {
            return response()->json([
                'status' => false,
                'code' => 'VALIDATION_FAILED',
                'message' => 'Idempotency-Key header is required.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_ids' => 'nullable|array',
            'customer_ids.*' => 'integer',
            'manual_phones' => 'nullable|array',
            'manual_phones.*' => 'string',
        ]);

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
}
