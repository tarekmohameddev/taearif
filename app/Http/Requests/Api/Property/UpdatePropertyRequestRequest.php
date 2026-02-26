<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdatePropertyRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status_id' => ['nullable', 'integer', 'exists:property_request_statuses,id'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'region' => ['nullable', 'integer'],
            'districts_id' => ['nullable', 'integer'],
            'area_from' => ['nullable', 'integer', 'min:0'],
            'area_to' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
