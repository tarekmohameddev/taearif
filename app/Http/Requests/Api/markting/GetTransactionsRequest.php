<?php

namespace App\Http\Requests\Api\markting;

use App\Http\Requests\Api\BaseApiFormRequest;

class GetTransactionsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'nullable|in:purchase,usage,refund,admin_add,admin_remove',
            'status' => 'nullable|in:pending,completed,failed,refunded',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
