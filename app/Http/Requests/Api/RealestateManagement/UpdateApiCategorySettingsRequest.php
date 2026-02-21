<?php

namespace App\Http\Requests\Api\RealestateManagement;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateApiCategorySettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:api_user_categories,id',
            'categories.*.is_active' => 'required|boolean',
            'show_even_if_empty' => 'nullable|boolean',
        ];
    }
}
