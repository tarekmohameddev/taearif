<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class AnalyticsIndexRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'action' => 'nullable|in:metrics,distributions,time_series,activity,pipeline_health',
            'timeRange' => 'nullable|array',
            'timeRange.timeRange' => ['nullable', 'string', 'in:today,yesterday,last7days,last30days,thisMonth,lastMonth,thisQuarter,lastQuarter,thisYear,lastYear,custom'],
            'timeRange.range' => ['nullable', 'string', 'in:today,yesterday,last7days,last30days,thisMonth,lastMonth,thisQuarter,lastQuarter,thisYear,lastYear,custom'],
            'timeRange.customStartDate' => 'nullable|date',
            'timeRange.customEndDate' => 'nullable|date',
            'interval' => 'nullable|in:day,week,month',
            'filters' => 'nullable|array',
            'filters.priority' => 'nullable|array',
            'filters.priority.*' => 'integer',
            'filters.source' => 'nullable|array',
            'filters.source.*' => 'string|max:50',
        ];
    }
}
