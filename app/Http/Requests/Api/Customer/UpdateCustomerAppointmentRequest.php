<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateCustomerAppointmentRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|max:100',
            'priority' => 'required|integer|in:1,2,3',
            'note' => 'nullable|string',
            'datetime' => 'sometimes|date',
            'duration' => 'sometimes|integer|min:1',
        ];
    }
}
