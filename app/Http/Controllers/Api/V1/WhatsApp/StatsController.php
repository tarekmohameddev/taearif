<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\WaAiResponseLog;
use App\Models\WaCampaign;
use App\Models\WaConversationState;
use App\Models\WaMessageLog;
use Illuminate\Http\JsonResponse;

class StatsController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        $totalCampaigns = WaCampaign::query()->where('user_id', $userId)->count();
        $totalSent = WaMessageLog::query()->where('user_id', $userId)->whereIn('status', ['sent', 'delivered'])->count();
        $totalDelivered = WaMessageLog::query()->where('user_id', $userId)->where('status', 'delivered')->count();
        $totalFailed = WaMessageLog::query()->where('user_id', $userId)->where('status', 'failed')->count();
        $thisMonthSent = WaMessageLog::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['sent', 'delivered'])
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $deliveryRate = $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0.0;

        return $this->ok([
            'total_campaigns' => $totalCampaigns,
            'total_sent' => $totalSent,
            'total_delivered' => $totalDelivered,
            'total_failed' => $totalFailed,
            'delivery_rate' => $deliveryRate,
            'this_month_sent' => $thisMonthSent,
        ]);
    }

    /**
     * Overview page KPI summary — all three stats in a single call.
     *
     * GET /api/v1/whatsapp/dashboard-summary
     */
    public function dashboardSummary(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        $botRepliesThisMonth = WaAiResponseLog::query()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        $activeConversations = WaConversationState::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->count();

        $messagesThisMonth = WaMessageLog::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['sent', 'delivered'])
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        return $this->ok([
            'bot_replies_this_month' => $botRepliesThisMonth,
            'active_conversations'   => $activeConversations,
            'messages_this_month'    => $messagesThisMonth,
        ]);
    }
}
