<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class CreateAppointmentRequest extends BaseApiFormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'type' => 'required|string|in:site_visit,office_meeting,phone_call,video_call,contract_signing,other',
            'datetime' => 'nullable|date|after:now',
            'duration' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ];
    }
}
