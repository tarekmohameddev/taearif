<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class BulkDismissRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'actionIds' => 'required|array|min:1|max:100',
            'actionIds.*' => 'string',
            'reason' => 'required|string|min:3|max:500',
        ];
    }
}
