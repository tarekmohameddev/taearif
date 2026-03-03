<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreCustomerStageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage_name' => 'required|string|max:255',
            'color' => 'nullable|string',
            'icon' => 'nullable|string',
            'order' => 'required|integer',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
