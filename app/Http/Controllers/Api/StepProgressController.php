<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Onboarding\CompleteOnboardingStepRequest;
use App\Services\SiteSetupProgressService;
use Illuminate\Http\Request;

class StepProgressController extends Controller
{
    public function __construct(
        private readonly SiteSetupProgressService $progressService,
    ) {}

    /**
     * Display site setup progress for the authenticated user's tenant owner.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSteps(Request $request)
    {
        $progress = $this->progressService->getProgress($request->user());

        if ($progress === null) {
            return response()->json([
                'message' => 'Unable to resolve tenant owner.',
            ], 403);
        }

        return response()->json($progress);
    }

    /**
     * Mark a setup step complete (owner user_steps write) and return GET-shaped progress.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeStep(CompleteOnboardingStepRequest $request)
    {
        $validated = $request->validated();
        $result = $this->progressService->completeStep($request->user(), $validated['step']);

        if (! $result['ok']) {
            return response()->json([
                'message' => $result['error'] ?? 'Unable to complete step.',
            ], $result['status'] ?? 422);
        }

        return response()->json(array_merge(
            ['message' => 'Step marked as completed.'],
            $result['progress']
        ));
    }
}
