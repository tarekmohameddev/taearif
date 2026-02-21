<?php

namespace App\Http\Requests\Api\App;

use App\Http\Requests\Api\BaseApiFormRequest;

class InstallAppRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'app_id' => 'required|exists:api_apps,id',
            'settings' => 'nullable|array',
            'settings.phone_number' => 'nullable|string|max:20',
            'settings.token' => 'nullable|string|max:255',
        ];
    }
}
