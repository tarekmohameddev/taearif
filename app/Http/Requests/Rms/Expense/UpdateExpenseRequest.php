<?php

namespace App\Http\Requests\Rms\Expense;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseRequest extends FormRequest
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
            'expense_name' => 'sometimes|string|max:255',
            'image_path' => 'nullable|string',
            'amount_type' => ['sometimes', RmsConstants::validationRule(RmsConstants::AMOUNT_TYPES)],
            'amount_value' => 'sometimes|numeric|min:0',
            'cost_center' => ['sometimes', RmsConstants::validationRule(RmsConstants::COST_CENTERS)],
            'is_active' => 'nullable|boolean',
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
            'expense_name.max' => 'The expense name cannot exceed 255 characters.',
            'amount_type.in' => 'The amount type must be either "percentage" or "fixed".',
            'amount_value.numeric' => 'The amount must be a number.',
            'amount_value.min' => 'The amount cannot be negative.',
            'cost_center.in' => 'The cost center must be either "tenant" or "owner".',
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
            'expense_name' => 'expense name',
            'amount_type' => 'amount type',
            'amount_value' => 'amount',
            'cost_center' => 'cost center',
            'is_active' => 'active status',
        ];
    }
}

