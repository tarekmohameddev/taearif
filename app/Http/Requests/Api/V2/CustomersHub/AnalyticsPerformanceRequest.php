<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class AnalyticsPerformanceRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'timeRange' => 'nullable|array',
            'timeRange.timeRange' => 'nullable|in:today,yesterday,last7days,last30days,thisMonth,lastMonth,thisQuarter,lastQuarter,thisYear,lastYear,custom',
            'timeRange.customStartDate' => 'nullable|date',
            'timeRange.customEndDate' => 'nullable|date',
        ];
    }
}
