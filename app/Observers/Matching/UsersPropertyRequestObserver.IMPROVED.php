<?php

namespace App\Observers\Matching;

use App\Models\Api\UserPropertyRequest;
use App\Services\Matching\MatchingService;
use App\Services\PropertyRequestCustomerService;
use Illuminate\Support\Facades\Log;

class UsersPropertyRequestObserver
{
    public function __construct(
        private PropertyRequestCustomerService $customerService,
        private MatchingService $matchingService
    ) {}

    public function created(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.created fired', ['id' => $model->id]);

        // Auto-create customer if setting is enabled
        $this->customerService->autoCreateFromRequest($model);

        // Generate property matches
        $this->matchingService->generateMatchesForRequest('web', $model->id, 25, true);
    }

    public function updated(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.updated fired', [
            'id' => $model->id,
            'changes' => $model->getChanges()
        ]);

        $this->matchingService->generateMatchesForRequest('web', $model->id, 25, true);
    }
}

