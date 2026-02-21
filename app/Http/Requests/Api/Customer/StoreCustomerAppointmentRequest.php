<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreCustomerAppointmentRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'priority' => 'required|integer|in:1,2,3',
            'note' => 'nullable|string',
            'datetime' => 'required|date',
            'duration' => 'required|integer|min:1',
            'source' => 'nullable|string|max:50',
        ];
    }
}
