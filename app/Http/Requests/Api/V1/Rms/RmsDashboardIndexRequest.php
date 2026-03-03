<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class RmsDashboardIndexRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'collections_period' => 'nullable|string|in:this_week,this_month,this_year,custom',
            'collections_from_date' => 'nullable|date|required_with:collections_to_date|required_if:collections_period,custom',
            'collections_to_date' => 'nullable|date|after_or_equal:collections_from_date|required_if:collections_period,custom',
            'payments_due_period' => 'nullable|string|in:this_week,this_month,this_year,custom',
            'payments_due_from_date' => 'nullable|date|required_with:payments_due_to_date|required_if:payments_due_period,custom',
            'payments_due_to_date' => 'nullable|date|after_or_equal:payments_due_from_date|required_if:payments_due_period,custom',
        ];
    }
}
