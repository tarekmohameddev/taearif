<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class ReorderFeaturedPropertiesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $payload = request()->all();
        if (isset($payload[0]) && is_array($payload[0])) {
            request()->merge($payload[0]);
        }
    }

    public function rules()
    {
        return [
            'id' => 'required|integer|exists:user_properties,id',
            'reorder_featured' => 'required|integer|min:1',
        ];
    }
}
