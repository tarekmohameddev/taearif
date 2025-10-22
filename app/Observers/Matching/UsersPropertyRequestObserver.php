<?php

namespace App\Observers\Matching;

use App\Models\Api\UserPropertyRequest;
use App\Services\Matching\MatchingService;
use Illuminate\Support\Facades\Log;

class UsersPropertyRequestObserver
{
    public function created(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.created fired', ['id' => $model->id]);
        app(MatchingService::class)->generateMatchesForRequest('web', $model->id, 25, true);
    }

    public function updated(UserPropertyRequest $model): void
    {
        Log::info('UsersPropertyRequestObserver.updated fired', ['id' => $model->id, 'changes' => $model->getChanges()]);
        app(MatchingService::class)->generateMatchesForRequest('web', $model->id, 25, true);
    }
}


