<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class ReorderCustomerTypesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'order' => 'required|array',
            'order.*' => 'integer|exists:users_api_customers_types,id',
        ];
    }
}
