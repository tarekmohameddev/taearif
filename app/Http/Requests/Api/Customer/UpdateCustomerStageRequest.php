<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateCustomerStageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage_name' => 'sometimes|string|max:255',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'order' => 'sometimes|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
