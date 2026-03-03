<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class ChangeCustomerTypeRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type_id' => 'required|integer',
        ];
    }
}
