<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class ListPaymentsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'payment_type' => 'nullable|string|in:rent,cost_item,platform_fee,water_fee,office_fee,deposit',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'include_reversed' => 'nullable|boolean',
        ];
    }
}
