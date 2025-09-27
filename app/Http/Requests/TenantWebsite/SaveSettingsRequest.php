<?php

namespace App\Http\Requests\TenantWebsite;

use Illuminate\Foundation\Http\FormRequest;

class SaveSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
        ];
    }
}


