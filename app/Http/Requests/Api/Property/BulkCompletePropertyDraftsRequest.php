<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class BulkCompletePropertyDraftsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'property_ids' => 'required|array',
            'property_ids.*' => 'integer|exists:user_properties,id',
        ];
    }
}
