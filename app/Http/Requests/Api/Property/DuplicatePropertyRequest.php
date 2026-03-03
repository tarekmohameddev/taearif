<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class DuplicatePropertyRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'nullable|max:255',
            'address' => 'nullable',
            'description' => 'nullable',
            'price' => 'nullable|numeric',
            'pricePerMeter' => 'nullable|numeric',
            'featured' => 'nullable|boolean',
        ];
    }
}
