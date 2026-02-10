<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\Crm\DefaultReminderTypeService;

class StoreReminderRequest extends FormRequest
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
                'required',
                'integer',
                Rule::exists('api_customers', 'id')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'reminder_type_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($userId) {
                    // If negative ID, check if it's a valid default type
                    if ($value < 0) {
                        if (!DefaultReminderTypeService::isDefaultTypeId($value)) {
                            $fail('The selected reminder type ID is invalid.');
                        }
                        return;
                    }
                    
                    // If positive ID, validate it exists in database
                    $exists = \DB::table('reminder_types')
                        ->where('id', $value)
                        ->where('user_id', $userId)
                        ->where('is_active', true)
                        ->exists();
                    
                    if (!$exists) {
                        $fail('The selected reminder type does not exist, does not belong to your account, or is inactive.');
                    }
                },
            ],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'datetime' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:now',
            ],
            'priority' => 'nullable|integer|in:0,1,2',
            'notes' => 'nullable|string',
            'source' => 'nullable|string|in:manual,website,whatsapp,affiliate',
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
            'datetime.after' => 'The reminder datetime must be a future date and time.',
            'priority.in' => 'The priority must be 0 (Low), 1 (Medium), or 2 (High).',
        ];
    }
}
