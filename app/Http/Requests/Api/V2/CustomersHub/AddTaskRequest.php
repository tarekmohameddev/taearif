<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class AddTaskRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => 'required|in:contact,office_visit,property_viewing',
            'datetime' => 'required|date',
            'notes' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:3',
        ];
    }
}
