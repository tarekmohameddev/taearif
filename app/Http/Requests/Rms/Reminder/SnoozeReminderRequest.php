<?php

namespace App\Http\Requests\Rms\Reminder;

use Illuminate\Foundation\Http\FormRequest;

class SnoozeReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'snooze_until' => 'required|date|after:today',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'snooze_until.required' => 'Please specify when you want to be reminded again.',
            'snooze_until.date' => 'The snooze date must be a valid date.',
            'snooze_until.after' => 'The snooze date must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'snooze_until' => 'snooze date',
        ];
    }
}

