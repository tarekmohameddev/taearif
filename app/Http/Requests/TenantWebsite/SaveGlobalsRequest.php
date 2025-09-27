<?php

namespace App\Http\Requests\TenantWebsite;

use Illuminate\Foundation\Http\FormRequest;

class SaveGlobalsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => 'required|array',
        ];
    }
}


