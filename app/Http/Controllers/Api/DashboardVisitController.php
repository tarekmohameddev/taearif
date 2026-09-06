<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardVisitService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DashboardVisitController extends Controller
{
    public function __invoke(Request $request, DashboardVisitService $visitService): Response
    {
        $user = $request->user();

        abort_unless($user, Response::HTTP_UNAUTHORIZED);

        $visitService->recordFor($user);

        return response()->noContent();
    }
}
