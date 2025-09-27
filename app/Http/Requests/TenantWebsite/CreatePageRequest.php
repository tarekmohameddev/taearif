<?php

namespace App\Http\Requests\TenantWebsite;

use Illuminate\Foundation\Http\FormRequest;

class CreatePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pageId' => 'required|string',
            'components' => 'required|array',
        ];
    }
}


