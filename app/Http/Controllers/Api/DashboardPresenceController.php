<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\DashboardPresenceService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardPresenceController extends Controller
{
    public function heartbeat(Request $request, DashboardPresenceService $presenceService): Response
    {
        $user = $request->user();

        abort_unless($user, Response::HTTP_UNAUTHORIZED);

        $result = $presenceService->record($user);

        if (! $result['available']) {
            return response()->json([
                'message' => 'Dashboard presence is unavailable.',
                'code' => 'dashboard_presence_unavailable',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (! $result['recorded']) {
            return response()->json([
                'message' => 'Dashboard presence is not allowed for this user.',
                'code' => 'dashboard_presence_forbidden',
            ], Response::HTTP_FORBIDDEN);
        }

        return response()->noContent();
    }
}
