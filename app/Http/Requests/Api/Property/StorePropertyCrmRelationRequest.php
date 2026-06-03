<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class StorePropertyCrmRelationRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_id' => 'required|integer|exists:crm_requests,id',
            'customer_id' => 'nullable|integer|exists:api_customers,id',
        ];
    }
}
