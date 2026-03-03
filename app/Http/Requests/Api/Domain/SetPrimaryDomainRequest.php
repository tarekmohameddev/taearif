<?php

namespace App\Http\Requests\Api\Domain;

use App\Http\Requests\Api\BaseApiFormRequest;

class SetPrimaryDomainRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|integer|exists:api_domains_settings,id',
        ];
    }
}
