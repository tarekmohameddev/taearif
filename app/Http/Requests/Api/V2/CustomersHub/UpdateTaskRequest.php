<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateTaskRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'datetime' => 'nullable|date',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:pending,completed,cancelled',
            'priority' => 'nullable|integer|min:0|max:3',
        ];
    }
}
