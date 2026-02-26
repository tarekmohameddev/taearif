<?php

namespace App\Http\Controllers\Api\V1\Email;

use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Email\Services\EmailSingleMessageService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Email\SendEmailMessageRequest;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use RuntimeException;

class MessageController extends BaseApiController
{
    public function __construct(private readonly EmailSingleMessageService $singleMessageService) {}

    public function send(SendEmailMessageRequest $request): JsonResponse
    {
        $key = trim((string) request()->header('Idempotency-Key', ''));
        $validated = $request->validated();
        $userId = (int) auth()->user()->tenantOwnerId();

        try {
            $data = $this->singleMessageService->send(
                $userId,
                $key,
                (string) $validated['recipient_email'],
                (string) $validated['subject'],
                (string) $validated['body_html'],
                $validated['body_text'] ?? null
            );
        } catch (IdempotencyConflictException $e) {
            return response()->json(['status' => false, 'code' => strtoupper((string) $e->reason), 'message' => $e->getMessage()], 409);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['status' => false, 'code' => 'INSUFFICIENT_CREDITS', 'message' => $e->getMessage()], 400);
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'code' => 'VALIDATION_FAILED', 'message' => $e->getMessage()], 422);
        } catch (RuntimeException $e) {
            return response()->json(['status' => false, 'code' => 'EMAIL_PROVIDER_FAILED', 'message' => $e->getMessage()], 502);
        }

        return response()->json(['status' => true, 'data' => $data], 202);
    }
}
