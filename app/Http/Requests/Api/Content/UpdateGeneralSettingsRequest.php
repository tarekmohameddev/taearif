<?php

namespace App\Http\Requests\Api\Content;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateGeneralSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'site_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|string|max:255',
            'favicon' => 'nullable|string|max:255',
            'maintenance_mode' => 'nullable|boolean',
            'show_breadcrumb' => 'nullable|boolean',
            'show_properties' => 'nullable|boolean',
            'additional_settings' => 'nullable|array',
            'color' => 'nullable|string|max:50',
            'primary_color' => 'nullable|string|max:50',
            'secondary_color' => 'nullable|string|max:50',
            'accent_color' => 'nullable|string|max:50',
        ];
    }
}
