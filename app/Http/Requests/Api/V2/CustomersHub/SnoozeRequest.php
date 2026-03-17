<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class SnoozeRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'snoozedUntil' => 'required|date|after:now',
            'reason'       => 'nullable|string|max:500',
        ];
    }
}

