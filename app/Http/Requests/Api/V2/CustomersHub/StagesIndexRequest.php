<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class StagesIndexRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'active_only' => 'nullable|in:true,false,1,0',
            'order_by' => 'nullable|string|in:order,created_at',
        ];
    }
}
