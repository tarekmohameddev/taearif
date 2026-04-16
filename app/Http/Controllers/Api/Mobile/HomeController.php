<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\UserPropertyRequest;
use App\Models\ApiCustomer;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $userId = $this->tenantUserId($request);

        $counts = Cache::remember("home_counts_{$userId}", 60, function () use ($userId) {
            return [
                'properties' => Property::where('user_id', $userId)->count(),
                'customers' => ApiCustomer::where('user_id', $userId)->count(),
                'active_rentals' => RmRental::where('user_id', $userId)->where('status', 'active')->count(),
                'open_property_requests' => UserPropertyRequest::where('user_id', $userId)
                    ->where('is_archived', false)
                    ->where('is_ignored', false)
                    ->count(),
            ];
        });

        $todayReminders = UserApiCustomerReminder::where('user_id', $userId)
            ->whereDate('datetime', today())
            ->with(['customer:id,name'])
            ->orderBy('datetime')
            ->limit(10)
            ->get(['id', 'title', 'customer_id', 'datetime'])
            ->map(function (UserApiCustomerReminder $r) {
                return [
                    'id' => (int) $r->id,
                    'title' => $r->title,
                    'customer_name' => $r->customer?->name,
                    'due_at' => $r->datetime?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $recentCustomers = ApiCustomer::where('user_id', $userId)
            ->with([
                'stage:id,stage_name',
            ])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'phone_number', 'stage_id', 'created_at'])
            ->map(function (ApiCustomer $c) {
                return [
                    'id' => (int) $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone_number,
                    'stage' => $c->stage?->stage_name,
                    'created_at' => $c->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        $recentPropertyRequests = UserPropertyRequest::where('user_id', $userId)
            ->with(['statusOption:id,name_ar,name_en'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'full_name', 'status_id', 'created_at'])
            ->map(function (UserPropertyRequest $r) {
                $statusName = $r->statusOption?->name_en ?? $r->statusOption?->name_ar;

                return [
                    'id' => (int) $r->id,
                    'customer_name' => $r->full_name,
                    'status' => $statusName,
                    'created_at' => $r->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return $this->success([
            'counts' => $counts,
            'today_reminders' => $todayReminders,
            'recent_customers' => $recentCustomers,
            'recent_property_requests' => $recentPropertyRequests,
        ]);
    }

    private function tenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? (int) $user->tenantOwnerId() : (int) $user->id;
    }
}
