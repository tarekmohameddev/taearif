<?php

namespace App\Http\Requests\Rms\Expense;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
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
            'expense_name' => 'required|string|max:255',
            'image_path' => 'nullable|string',
            'amount_type' => ['required', RmsConstants::validationRule(RmsConstants::AMOUNT_TYPES)],
            'amount_value' => 'required|numeric|min:0',
            'cost_center' => ['required', RmsConstants::validationRule(RmsConstants::COST_CENTERS)],
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
            'expense_name.required' => 'Please provide a name for the expense.',
            'expense_name.max' => 'The expense name cannot exceed 255 characters.',
            'amount_type.required' => 'Please specify whether the amount is a percentage or fixed amount.',
            'amount_type.in' => 'The amount type must be either "percentage" or "fixed".',
            'amount_value.required' => 'Please provide the expense amount.',
            'amount_value.numeric' => 'The amount must be a number.',
            'amount_value.min' => 'The amount cannot be negative.',
            'cost_center.required' => 'Please specify who bears this cost.',
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

