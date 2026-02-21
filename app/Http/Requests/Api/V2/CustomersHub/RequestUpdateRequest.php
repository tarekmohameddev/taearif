<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class RequestUpdateRequest extends BaseApiFormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high',
            'notes' => 'nullable|string',
            'duration' => 'nullable|integer|min:0',
            'status_id' => 'nullable|integer',
            'stage_id' => 'nullable|string|max:50',
        ];
    }
}
