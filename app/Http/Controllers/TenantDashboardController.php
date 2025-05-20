<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleAnalyticsService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TenantDashboardController extends Controller
{
    public function dashboard(Request $request, GoogleAnalyticsService $analyticsService)
    {
        // $tenant = Auth::user(); // Assuming username is tenant ID
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();
        $username = "lira";
        $analyticsData = $analyticsService->getDashboardData($username, $startDate, $endDate);

        return response()->json($analyticsData);

        // return view('tenant.dashboard', compact('analyticsData'));
    }
}

