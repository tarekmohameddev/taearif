<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleAnalyticsService;
use Carbon\Carbon;

class TenantDashboardController extends Controller
{
    public function dashboard(Request $request, GoogleAnalyticsService $analyticsService)
    {
        $startDate = Carbon::now()->subDays(7); // 7 days ago
        $endDate = Carbon::now();

        // $tenantId = 'lira'; // use static for now
        $fullHost = request()->getHost();
        \Log::info('Full Host: ' . $fullHost);
        $tenantId = explode('.', $fullHost)[0];
        $analyticsData = $analyticsService->getDashboardData($tenantId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'tenant' => $tenantId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $analyticsData
        ]);
    }

}


