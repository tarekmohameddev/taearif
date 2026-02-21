<?php

namespace App\Http\Requests\Api\Content;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateCustomerDropdownSettingsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'is_visible' => 'boolean',
            'show_login' => 'boolean',
            'show_register' => 'boolean',
            'show_dashboard' => 'boolean',
            'show_logout' => 'boolean',
            'additional_settings' => 'nullable|array',
            'additional_settings.button_text' => 'nullable|string|max:50',
            'additional_settings.button_style' => 'nullable|string|in:primary,secondary,outline,link',
            'additional_settings.dropdown_position' => 'nullable|string|in:left,right,center',
        ];
    }
}
