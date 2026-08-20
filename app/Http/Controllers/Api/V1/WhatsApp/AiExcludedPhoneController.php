<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\StoreAiExcludedPhoneRequest;
use App\Models\WaAiExcludedPhone;
use App\Models\WaNumber;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

final class AiExcludedPhoneController extends BaseApiController
{
    public function index(int $numberId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        if (! WaNumber::where('id', $numberId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }

        $phones = WaAiExcludedPhone::where('wa_number_id', $numberId)
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get(['id', 'phone', 'created_at']);

        return $this->ok($phones);
    }

    public function store(StoreAiExcludedPhoneRequest $request, int $numberId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        if (! WaNumber::where('id', $numberId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }

        $phone = $this->normalizePhone($request->validated('phone'));

        if ($phone === '') {
            return response()->json(['status' => 'error', 'code' => 'INVALID_PHONE', 'message' => 'Phone number must contain digits.'], 422);
        }

        try {
            $record = WaAiExcludedPhone::create([
                'user_id'      => $userId,
                'wa_number_id' => $numberId,
                'phone'        => $phone,
            ]);
        } catch (QueryException $e) {
            // MySQL error 1062 = duplicate entry (unique constraint violation)
            if ($e->errorInfo[1] === 1062) {
                return response()->json(['status' => 'error', 'code' => 'PHONE_ALREADY_EXCLUDED', 'message' => 'This phone number is already in the exclusion list.'], 422);
            }
            throw $e;
        }

        return response()->json(['status' => 'ok', 'data' => $record], 201);
    }

    public function destroy(int $numberId, int $phoneId): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        if (! WaNumber::where('id', $numberId)->where('user_id', $userId)->exists()) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
        }

        $deleted = WaAiExcludedPhone::where('id', $phoneId)
            ->where('wa_number_id', $numberId)
            ->where('user_id', $userId)
            ->delete();

        if (! $deleted) {
            return response()->json(['status' => 'error', 'code' => 'NOT_FOUND', 'message' => 'Excluded phone record not found.'], 404);
        }

        return response()->json(null, 204);
    }

    private function normalizePhone(string $raw): string
    {
        return preg_replace('/[^\d]/', '', $raw) ?? '';
    }
}
