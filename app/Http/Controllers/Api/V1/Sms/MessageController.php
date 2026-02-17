<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Sms\Services\SmsSingleMessageService;
use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RuntimeException;

class MessageController extends BaseApiController
{
    public function __construct(private readonly SmsSingleMessageService $singleMessageService) {}

    public function send(Request $request): JsonResponse
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
            'recipient_phone' => 'required|string',
            'content' => 'required|string',
        ]);

        $userId = (int) auth()->user()->tenantOwnerId();

        try {
            $data = $this->singleMessageService->send(
                $userId,
                $key,
                (string) $validated['recipient_phone'],
                (string) $validated['content']
            );
        } catch (IdempotencyConflictException $e) {
            return response()->json(['status' => false, 'code' => strtoupper((string) $e->reason), 'message' => $e->getMessage()], 409);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['status' => false, 'code' => 'INSUFFICIENT_CREDITS', 'message' => $e->getMessage()], 400);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['status' => false, 'code' => 'SMS_PROVIDER_FAILED', 'message' => $e->getMessage()], 502);
        }

        return response()->json(['status' => true, 'data' => $data], 202);
    }
}

