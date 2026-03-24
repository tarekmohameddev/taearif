<?php

namespace App\Observers\Matching;

use App\Models\Api\UserPropertyRequest;
use App\Services\Matching\MatchingService;
use App\Services\Matching\RequestCompletenessService;
use App\Services\PropertyRequestCustomerService;
use Illuminate\Support\Facades\Log;

class UsersPropertyRequestObserver
{
    public function __construct(
        private PropertyRequestCustomerService $customerService,
        private MatchingService $matchingService,
        private RequestCompletenessService $completeness
    ) {}

    public function created(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.created fired', ['id' => $model->id]);

        if ($model->is_ignored) {
            Log::info('UsersPropertyRequestObserver: skipping matching for ignored request', ['id' => $model->id]);
            return;
        }

        // Auto-create customer if setting is enabled
        $this->customerService->autoCreateFromRequest($model);

        $check = $this->completeness->validate('web', $model->id);
        Log::info('UsersPropertyRequestObserver: completeness check', [
            'id' => $model->id,
            'is_complete' => $check['is_complete'],
            'missing_fields' => $check['missing_fields'],
        ]);

        // Generate property matches
        $forceAi = (bool) $check['is_complete'];
        $limit = $forceAi ? 25 : 10;
        $this->matchingService->generateMatchesForRequest('web', $model->id, $limit, $forceAi, $model->user_id);
    }

    public function updated(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.updated fired', ['id' => $model->id, 'changes' => $model->getChanges()]);

        if ($model->is_ignored) {
            Log::info('UsersPropertyRequestObserver: skipping matching for ignored request', ['id' => $model->id]);
            return;
        }

        $check = $this->completeness->validate('web', $model->id);
        Log::info('UsersPropertyRequestObserver: completeness check', [
            'id' => $model->id,
            'is_complete' => $check['is_complete'],
            'missing_fields' => $check['missing_fields'],
        ]);

        $forceAi = (bool) $check['is_complete'];
        $limit = $forceAi ? 25 : 10;
        $this->matchingService->generateMatchesForRequest('web', $model->id, $limit, $forceAi, $model->user_id);
    }

}


