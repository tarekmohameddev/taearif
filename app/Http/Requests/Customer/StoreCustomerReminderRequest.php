<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerReminderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
        return [
            'customer_id' => 'nullable|integer', // Make nullable for general reminders
            'title'       => 'required_without:reminder_id|string|max:255', // Required if not cloning
            'priority'    => 'nullable|integer|in:1,2,3', // 1=low, 2=medium, 3=high
            'datetime'    => 'required|date',
            'reminder_id' => 'nullable|integer|exists:users_api_customers_reminders,id', // For cloning
        ];
    }
}

