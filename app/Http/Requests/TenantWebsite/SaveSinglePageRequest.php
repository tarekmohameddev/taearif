<?php

namespace App\Http\Requests\TenantWebsite;

use Illuminate\Foundation\Http\FormRequest;

class SaveSinglePageRequest extends FormRequest
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
            'components.*.id' => 'required|string',
            'components.*.type' => 'required|string',
            'components.*.name' => 'required|string',
            'components.*.componentName' => 'required|string',
            'components.*.data' => 'required|array',
            'components.*.position' => 'required|integer',
        ];
    }
}


