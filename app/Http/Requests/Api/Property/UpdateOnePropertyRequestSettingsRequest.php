<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateOnePropertyRequestSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'is_visible'    => ['nullable', 'boolean'],
            'is_required'   => ['nullable', 'boolean'],
            'sort_order'    => ['nullable', 'integer'],
            'label_ar'      => ['nullable', 'string', 'max:255'],
            'label_en'      => ['nullable', 'string', 'max:255'],
            'meta'          => ['nullable', 'array'],
        ];
    }
}
