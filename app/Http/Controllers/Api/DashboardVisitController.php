<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardVisitService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardVisitController extends Controller
{
    public function __invoke(Request $request, DashboardVisitService $visitService): Response
    {
        $user = $request->user();

        abort_unless($user, Response::HTTP_UNAUTHORIZED);

        $result = $visitService->recordEligibleVisit($user);

        if (! $result['recorded']) {
            return response()->json([
                'message' => 'Dashboard visit is not allowed for this user.',
                'code' => 'dashboard_visit_forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->noContent();
    }
}
