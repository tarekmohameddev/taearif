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

        // $analyticsData = $analyticsService->getEventCountsByName($tenantId, $startDate, $endDate);

        $analyticsData = $analyticsService->getEventCountsByName( now()->subDays(30), now(), $tenantId);
        $paramCounts = $analyticsService->getEventParameterCounts($startDate, $endDate, 'user_engagement', 'tenant_id', $tenantId);

        return response()->json([
            'status' => 'success',
            'tenant' => $tenantId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $paramCounts
        ]);
    }


}


