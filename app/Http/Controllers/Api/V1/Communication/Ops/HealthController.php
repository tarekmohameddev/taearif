<?php

namespace App\Http\Controllers\Api\V1\Communication\Ops;

use App\Http\Controllers\Controller;
use App\Models\CommunicationDeliveryAttempt;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    /**
     * Lightweight ops health: recent-window metrics only (indexed, no full table scans).
     */
    public function __invoke(): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $window = now()->subHours(24);

        $attemptsQuery = CommunicationDeliveryAttempt::query()
            ->where('user_id', $userId)
            ->where('dispatched_at', '>=', $window);

        $total = (clone $attemptsQuery)->count();
        $failed = (clone $attemptsQuery)->where('attempt_status', 'failed')->count();
        $dueRetry = CommunicationDeliveryAttempt::query()
            ->where('user_id', $userId)
            ->where('attempt_status', 'retry_scheduled')
            ->where('retry_eligible', true)
            ->where('next_retry_at', '<=', now())
            ->where('created_at', '>=', $window)
            ->count();

        $failureRatio = $total > 0 ? round($failed / $total, 4) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'window_hours' => 24,
                'attempts_total' => $total,
                'attempts_failed' => $failed,
                'failure_ratio' => $failureRatio,
                'due_retry_backlog' => $dueRetry,
            ],
        ]);
    }
}
