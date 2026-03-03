<?php

namespace App\Http\Controllers\Api\V1\Communication\Ops;

use App\Http\Controllers\Controller;
use App\Models\CommunicationWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookEventsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        $query = CommunicationWebhookEvent::query()->where('user_id', $userId);

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }
        if ($request->filled('from_date')) {
            $query->where('received_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->where('received_at', '<=', $request->input('to_date'));
        }

        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);
        $items = $query->orderByDesc('received_at')->paginate($perPage);

        $data = $items->getCollection()->map(fn (CommunicationWebhookEvent $e) => [
            'id' => $e->id,
            'channel' => $e->channel,
            'provider' => $e->provider,
            'event_type' => $e->event_type,
            'provider_event_id' => $e->provider_event_id,
            'provider_message_id' => $e->provider_message_id,
            'signature_valid' => $e->signature_valid,
            'tenant_resolved' => $e->tenant_resolved,
            'processing_result' => $e->processing_result,
            'received_at' => $e->received_at?->toIso8601String(),
            'processed_at' => $e->processed_at?->toIso8601String(),
            'created_at' => $e->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'events' => $data,
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
