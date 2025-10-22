<?php

namespace App\Http\Requests\Rms\Rental;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalRequest extends FormRequest
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
            'tenant_full_name' => 'sometimes|string|max:150',
            'tenant_phone' => 'sometimes|string|max:32',
            'tenant_email' => 'nullable|email',
            'tenant_job_title' => 'nullable|string|max:120',
            'tenant_social_status' => ['nullable', RmsConstants::validationRule(RmsConstants::SOCIAL_STATUSES)],
            'tenant_national_id' => 'nullable|string|max:20',
            'unit_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'building_id' => 'nullable',
            'move_in_date' => 'nullable|date',
            'rental_type' => ['sometimes', RmsConstants::validationRule(RmsConstants::RENTAL_TYPES)],
            'rental_duration' => 'sometimes|integer|min:1',
            'paying_plan' => ['sometimes', RmsConstants::validationRule(RmsConstants::PAYING_PLANS)],
            'total_rental_amount' => 'sometimes|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'contract_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'cost_items' => 'nullable|array',
            'cost_items.*.name' => 'required|string|max:255',
            'cost_items.*.cost' => 'required|numeric|min:0',
            'cost_items.*.type' => ['required', RmsConstants::validationRule(RmsConstants::COST_ITEM_TYPES)],
            'cost_items.*.payer' => ['required', RmsConstants::validationRule(RmsConstants::PAYERS)],
            'cost_items.*.payment_frequency' => ['required', RmsConstants::validationRule(RmsConstants::PAYMENT_FREQUENCIES)],
            'cost_items.*.percentage_of' => 'nullable|numeric|min:0',
            'cost_items.*.description' => 'nullable|string',
            'payments' => 'nullable|array',
            'regenerate_schedule' => 'nullable|boolean',
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
            'tenant_full_name.max' => 'The tenant name cannot exceed 150 characters.',
            'tenant_phone.max' => 'The phone number cannot exceed 32 characters.',
            'tenant_email.email' => 'Please provide a valid email address.',
            'rental_duration.min' => 'Rental duration must be at least 1 period.',
            'total_rental_amount.numeric' => 'The rental amount must be a number.',
            'total_rental_amount.min' => 'The rental amount cannot be negative.',
            'currency.size' => 'Currency code must be exactly 3 characters.',
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
            'tenant_full_name' => 'tenant name',
            'tenant_phone' => 'phone number',
            'tenant_email' => 'email address',
            'rental_duration' => 'duration',
            'paying_plan' => 'payment plan',
            'total_rental_amount' => 'rental amount',
        ];
    }
}

