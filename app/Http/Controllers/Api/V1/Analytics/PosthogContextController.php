<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Analytics\PosthogContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PosthogContextController extends BaseApiController
{
    public function __construct(
        protected PosthogContextService $posthogContextService
    ) {}

    /**
     * GET /api/user/posthog-context
     *
     * Bootstrap PostHog for the tenant SPA (app.taearif.com).
     */
    public function show(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return $this->error('Unauthorized access', 401);
        }

        return $this->success(
            $this->posthogContextService->forUser($user),
            'PostHog context retrieved successfully'
        );
    }
}
