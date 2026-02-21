<?php

namespace App\Http\Requests\Api\App;

use Illuminate\Foundation\Http\FormRequest;

class InstallWhatsappRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'settings' => 'nullable|array',
            'settings.phone_number' => 'nullable|string|max:20',
            'settings.token' => 'nullable|string|max:255',
        ];
    }
}
