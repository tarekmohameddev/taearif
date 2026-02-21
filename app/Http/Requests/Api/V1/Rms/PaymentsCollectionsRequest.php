<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class PaymentsCollectionsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'period' => 'nullable|string|in:this_week,this_month,this_year,custom',
            'from_date' => 'nullable|date|required_if:period,custom',
            'to_date' => 'nullable|date|after_or_equal:from_date|required_if:period,custom',
        ];
    }
}
