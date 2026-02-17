<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use Illuminate\Http\JsonResponse;

class StatsController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();

        $totalCampaigns = SmsCampaign::query()->where('user_id', $userId)->count();
        $totalSent = SmsMessageLog::query()->where('user_id', $userId)->whereIn('status', ['sent', 'delivered'])->count();
        $totalDelivered = SmsMessageLog::query()->where('user_id', $userId)->where('status', 'delivered')->count();
        $totalFailed = SmsMessageLog::query()->where('user_id', $userId)->where('status', 'failed')->count();
        $thisMonthSent = SmsMessageLog::query()
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
}

