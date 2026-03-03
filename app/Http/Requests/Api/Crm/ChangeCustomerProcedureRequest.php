<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class ChangeCustomerProcedureRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'procedure_id' => 'required|integer|exists:users_api_customers_procedures,id',
        ];
    }
}
