<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleAnalyticsService;
use Carbon\Carbon;

class TenantDashboardController extends Controller
{
    public function dashboard(Request $request, GoogleAnalyticsService $analyticsService)
    {
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        // Use a static tenant for testing or dynamic later via auth()->user()->username
        $tenantId = "lira";

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


