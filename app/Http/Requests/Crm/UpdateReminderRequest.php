<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->tenantOwnerId();

        return [
            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('api_customers', 'id')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'reminder_type_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('reminder_types', 'id')
                    ->where('user_id', $userId)
                    ->where('is_active', true),
            ],
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'datetime' => [
                'sometimes',
                'required',
                'date_format:Y-m-d H:i:s',
            ],
            'priority' => 'nullable|integer|in:0,1,2',
            'status' => 'nullable|string|in:pending,completed,overdue,cancelled',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'The customer ID field is required.',
            'customer_id.exists' => 'The selected customer does not exist or does not belong to your account.',
            'reminder_type_id.required' => 'The reminder type ID field is required.',
            'reminder_type_id.exists' => 'The selected reminder type does not exist, does not belong to your account, or is inactive.',
            'title.required' => 'The title field is required.',
            'title.max' => 'The title may not be greater than 255 characters.',
            'datetime.required' => 'The datetime field is required.',
            'datetime.date_format' => 'The datetime must be in the format: Y-m-d H:i:s (e.g., 2026-11-11 11:11:00).',
            'priority.in' => 'The priority must be 0 (Low), 1 (Medium), or 2 (High).',
            'status.in' => 'The status must be one of: pending, completed, overdue, cancelled.',
        ];
    }
}
