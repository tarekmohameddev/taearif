<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

class CancelSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_type' => ['required', 'string', 'in:immediate,end_of_period'],
            'reason' => ['required', 'string', 'max:500'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_type.required' => 'Cancel type is required',
            'cancel_type.in' => 'Cancel type must be immediate or end_of_period',
            'reason.required' => 'Cancellation reason is required',
        ];
    }
}

