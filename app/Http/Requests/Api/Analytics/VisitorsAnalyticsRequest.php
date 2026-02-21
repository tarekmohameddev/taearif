<?php

namespace App\Http\Requests\Api\Analytics;

use App\Http\Requests\Api\BaseApiFormRequest;

class VisitorsAnalyticsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'tenant_id' => 'nullable|string|max:255',
            'time_range' => 'nullable|integer|in:7,30,90,365',
        ];
    }
}
