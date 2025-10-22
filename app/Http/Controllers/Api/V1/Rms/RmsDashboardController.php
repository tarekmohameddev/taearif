<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Services\Rms\DashboardService;

class RmsDashboardController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $range = (int) $request->get('range', 7); // 7 or 30 days
            $data = $this->dashboardService->getDashboardData($this->getUserId(), $range);

            return $this->success($data);
        }, 'retrieve dashboard data');
    }
}

