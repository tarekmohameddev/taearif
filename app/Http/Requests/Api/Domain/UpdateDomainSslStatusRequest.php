<?php

namespace App\Http\Requests\Api\Domain;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateDomainSslStatusRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'domain_id' => 'required|exists:api_domains_settings,id',
            'ssl' => 'required|boolean',
        ];
    }
}
