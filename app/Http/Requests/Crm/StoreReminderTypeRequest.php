<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReminderTypeRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('reminder_types', 'name')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'name_ar' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'icon' => 'nullable|string|max:100',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
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
            'name.required' => 'The name field is required.',
            'name.unique' => 'A reminder type with this name already exists for your account.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'name_ar.max' => 'The Arabic name may not be greater than 255 characters.',
            'color.regex' => 'The color must be a valid hex color code (e.g., #ff0000 or #f00).',
            'icon.max' => 'The icon may not be greater than 100 characters.',
            'order.integer' => 'The order must be an integer.',
            'order.min' => 'The order must be a positive integer (0 or greater).',
        ];
    }
}
