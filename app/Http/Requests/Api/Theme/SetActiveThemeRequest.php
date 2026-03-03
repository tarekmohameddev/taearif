<?php

namespace App\Http\Requests\Api\Theme;

use App\Http\Requests\Api\BaseApiFormRequest;

class SetActiveThemeRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'theme_id' => 'required|exists:api_themes_settings,theme_id',
        ];
    }
}
