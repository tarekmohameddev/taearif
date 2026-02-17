<?php

namespace App\Http\Controllers\Api\V1\Communication\Ops;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\SmsMessageLog;
use Illuminate\Http\JsonResponse;

class ReconciliationSummaryController extends Controller
{
    /**
     * Summary of sent-but-not-delivered items in lookback window (indexed).
     */
    public function __invoke(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $lookbackDays = (int) config('communication.reliability.reconcile.lookback_days', 30);
        $cutoff = now()->subDays($lookbackDays);

        $messagesSent = Message::query()
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where('created_at', '>=', $cutoff)
            ->count();

        $smsSent = SmsMessageLog::query()
            ->where('user_id', $userId)
            ->where('status', 'sent')
            ->where('created_at', '>=', $cutoff)
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'lookback_days' => $lookbackDays,
                'whatsapp_sent_not_delivered' => $messagesSent,
                'sms_sent_not_delivered' => $smsSent,
            ],
        ]);
    }
}
