<?php

namespace App\Http\Controllers\Api\V1\Communication\Ops;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SmsMessageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StuckItemsController extends Controller
{
    /**
     * Items in sent state beyond threshold with no delivery update (bounded query).
     */
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $lookbackDays = (int) config('communication.reliability.reconcile.lookback_days', 30);
        $cutoff = now()->subDays($lookbackDays);
        $limit = min(max((int) $request->input('limit', 50), 1), 200);

        $messages = Message::query()
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'conversation_id', 'provider_message_id', 'status', 'created_at']);

        $smsLogs = SmsMessageLog::query()
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get(['id', 'gateway_message_id', 'status', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'lookback_days' => $lookbackDays,
                'messages' => $messages->map(fn ($m) => [
                    'id' => $m->id,
                    'conversation_id' => $m->conversation_id,
                    'provider_message_id' => $m->provider_message_id,
                    'status' => $m->status,
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
                'sms_logs' => $smsLogs->map(fn ($s) => [
                    'id' => $s->id,
                    'gateway_message_id' => $s->gateway_message_id,
                    'status' => $s->status,
                    'created_at' => $s->created_at?->toIso8601String(),
                ]),
            ],
        ]);
    }
}
