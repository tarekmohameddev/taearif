<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class BulkDestroyPropertyDraftsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'property_ids' => 'required|array|min:1|max:100',
            'property_ids.*' => 'integer|distinct',
        ];
    }
}
