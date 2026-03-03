<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdatePreferencesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'propertyType' => 'nullable|string|max:50',
            'budget' => 'nullable|numeric',
            'bedrooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'message' => 'nullable|string|max:5000',
        ];
    }
}
