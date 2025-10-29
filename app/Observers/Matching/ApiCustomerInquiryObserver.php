<?php

namespace App\Observers\Matching;

use App\Models\Api\ApiCustomerInquiry;
use App\Services\Matching\MatchingService;
use Illuminate\Support\Facades\Log;

class ApiCustomerInquiryObserver
{
    public function created(ApiCustomerInquiry $model): void
    {
        Log::info('ApiCustomerInquiryObserver.created fired', ['id' => $model->id]);
        app(MatchingService::class)->generateMatchesForRequest('whatsapp', $model->id, 25, true);
    }

    public function updated(ApiCustomerInquiry $model): void
    {
        Log::info('ApiCustomerInquiryObserver.updated fired', ['id' => $model->id, 'changes' => $model->getChanges()]);
        app(MatchingService::class)->generateMatchesForRequest('whatsapp', $model->id, 25, true);
    }
}


