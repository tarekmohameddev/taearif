<?php

namespace App\Observers\Matching;

use App\Models\Api\ApiCustomerInquiry;
use App\Services\Matching\MatchingService;
use App\Services\Matching\RequestCompletenessService;
use Illuminate\Support\Facades\Log;

class ApiCustomerInquiryObserver
{
    public function __construct(
        private MatchingService $matchingService,
        private RequestCompletenessService $completeness,
    ) {}

    public function created(ApiCustomerInquiry $model): void
    {
        Log::info('ApiCustomerInquiryObserver.created fired', ['id' => $model->id]);

        $check = $this->completeness->validate('whatsapp', $model->id);
        Log::info('ApiCustomerInquiryObserver: completeness check', [
            'id' => $model->id,
            'is_complete' => $check['is_complete'],
            'missing_fields' => $check['missing_fields'],
        ]);

        $forceAi = (bool) $check['is_complete'];
        $limit = $forceAi ? 25 : 10;
        $this->matchingService->generateMatchesForRequest('whatsapp', $model->id, $limit, $forceAi, $model->user_id);
    }

    public function updated(ApiCustomerInquiry $model): void
    {
        Log::info('ApiCustomerInquiryObserver.updated fired', ['id' => $model->id, 'changes' => $model->getChanges()]);

        $check = $this->completeness->validate('whatsapp', $model->id);
        Log::info('ApiCustomerInquiryObserver: completeness check', [
            'id' => $model->id,
            'is_complete' => $check['is_complete'],
            'missing_fields' => $check['missing_fields'],
        ]);

        $forceAi = (bool) $check['is_complete'];
        $limit = $forceAi ? 25 : 10;
        $this->matchingService->generateMatchesForRequest('whatsapp', $model->id, $limit, $forceAi, $model->user_id);
    }
}


