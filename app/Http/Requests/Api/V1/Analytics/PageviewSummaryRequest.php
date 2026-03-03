<?php

namespace App\Http\Requests\Api\V1\Analytics;

use App\Http\Requests\Api\BaseApiFormRequest;

class PageviewSummaryRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_date' => 'required|date|date_format:Y-m-d',
            'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
        ];
    }
}
