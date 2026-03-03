<?php

namespace App\Http\Requests\Api\V1\TenantWebsite;

use Illuminate\Foundation\Http\FormRequest;

class GetTenantRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'websiteName' => 'required|string',
        ];
    }
}
