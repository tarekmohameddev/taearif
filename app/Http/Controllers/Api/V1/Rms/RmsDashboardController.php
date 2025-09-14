<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\DashboardService;

class RmsDashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        $range = (int) $request->get('range', 7); // 7 or 30 days
        $perPage = (int) $request->get('per_page', 10); // Default 10 items per page
        $page = (int) $request->get('page', 1); // Default page 1
        
        $data = $this->dashboardService->getDashboardData(auth()->id(), $range, $perPage, $page);

        return response()->json(['status' => true, 'data' => $data]);
    }
}

