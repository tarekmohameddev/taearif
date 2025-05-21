<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleAnalyticsService;
use Carbon\Carbon;

class TenantDashboardController extends Controller
{
    public function dashboard(Request $request, GoogleAnalyticsService $analyticsService)
    {
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $tenantId = 'ress'; // Hardcoded tenant for testing

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


