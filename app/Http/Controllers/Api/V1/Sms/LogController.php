<?php

namespace App\Http\Controllers\Api\V1\Sms;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SmsMessageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $query = SmsMessageLog::query()->where('user_id', $userId);

        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', (int) $request->input('campaign_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', (string) $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', (string) $request->input('to_date'));
        }

        $items = $query->orderByDesc('id')->paginate($perPage);

        return $this->ok($items);
    }
}

