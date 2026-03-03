<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class PaymentReportRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'property_id' => 'nullable|integer|exists:user_properties,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'building_id' => 'nullable|integer|exists:buildings,id',
        ];
    }
}
