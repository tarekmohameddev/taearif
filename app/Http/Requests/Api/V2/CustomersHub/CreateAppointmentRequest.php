<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class CreateAppointmentRequest extends BaseApiFormRequest
{
    public function authorize() { return true; }

    protected function prepareForValidation(): void
    {
        // Accept legacy "date" field as an alias when "datetime" is omitted.
        if ($this->filled('date') && !$this->filled('datetime')) {
            $this->merge(['datetime' => $this->input('date')]);
        }
    }

    public function rules()
    {
        return [
            'type' => 'required|string|in:site_visit,office_meeting,phone_call,video_call,contract_signing,other',
            'date' => 'nullable|date',
            // Date-only values (e.g. "2027-12-11") are stored at midnight in the app timezone.
            'datetime' => 'nullable|date',
            'duration' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ];
    }
}
