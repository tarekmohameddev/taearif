<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class CreateReminderRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'datetime' => 'required|date|after:now',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'type' => 'required|string|in:follow_up,payment_due,document_required,other',
            'notes' => 'nullable|string',
        ];
    }
}
