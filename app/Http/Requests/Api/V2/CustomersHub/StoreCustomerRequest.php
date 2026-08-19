<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreCustomerRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'note' => 'nullable|string',
            'customers_hub_stage_id' => 'nullable|string|max:50|exists:customers_hub_stages,stage_id',
            'priority_id' => 'nullable|integer',
        ];
    }
}
