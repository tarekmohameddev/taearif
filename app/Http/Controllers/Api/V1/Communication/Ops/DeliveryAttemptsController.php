<?php

namespace App\Http\Controllers\Api\V1\Communication\Ops;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDeliveryAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryAttemptsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        $query = CommunicationDeliveryAttempt::query()->where('user_id', $userId);

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }
        if ($request->filled('attempt_status')) {
            $query->where('attempt_status', $request->input('attempt_status'));
        }
        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->input('to_date'));
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $items = $query->orderByDesc('id')->paginate($perPage);

        $data = $items->getCollection()->map(function (CommunicationDeliveryAttempt $a) {
            return [
                'id' => $a->id,
                'channel' => $a->channel,
                'provider' => $a->provider,
                'subject_type' => $a->subject_type,
                'subject_id' => $a->subject_id,
                'attempt_no' => $a->attempt_no,
                'attempt_status' => $a->attempt_status,
                'retry_eligible' => $a->retry_eligible,
                'provider_message_id' => $a->provider_message_id,
                'is_transient_failure' => $a->is_transient_failure,
                'error_code' => $a->error_code,
                'error_message' => $a->error_message,
                'next_retry_at' => $a->next_retry_at?->toIso8601String(),
                'dispatched_at' => $a->dispatched_at?->toIso8601String(),
                'completed_at' => $a->completed_at?->toIso8601String(),
                'created_at' => $a->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'attempts' => $data,
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }
}
