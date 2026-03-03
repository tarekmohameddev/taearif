<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class ReorderCrmRequestsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage_id' => ['required', 'integer', 'exists:users_api_customers_stages,id'],
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'distinct'],
        ];
    }
}
