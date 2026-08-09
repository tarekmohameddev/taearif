<?php

namespace App\Http\Requests\Api\Domain;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreDomainSettingRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'custom_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i',
            ],
        ];
    }
}
