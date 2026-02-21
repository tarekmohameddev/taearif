<?php

namespace App\Http\Requests\Api\Content;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateAboutSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'history' => 'nullable|string',
            'mission' => 'nullable|string',
            'vision' => 'nullable|string',
            'image_path' => 'nullable|string',
            'features' => 'required|array',
            'features.*.id' => 'required|integer',
            'features.*.title' => 'required|string|max:255',
            'features.*.description' => 'required|string',
            'status' => 'required|boolean',
        ];
    }
}
