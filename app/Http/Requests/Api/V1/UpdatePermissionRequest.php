<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdatePermissionRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $id = request()->route('id');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:api_permissions,name,' . $id],
            'description' => ['nullable', 'string', 'max:500'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
        ];
    }
}
