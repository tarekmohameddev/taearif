<?php

namespace App\Http\Requests\Rms\Rental;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
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
            'tenant_full_name' => 'required|string|max:150',
            'tenant_phone' => 'required|string|max:32',
            'tenant_email' => 'nullable|email',
            'tenant_job_title' => 'nullable|string|max:120',
            'tenant_social_status' => ['nullable', RmsConstants::validationRule(RmsConstants::SOCIAL_STATUSES)],
            'tenant_national_id' => 'nullable|string|max:20',
            'unit_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'building_id' => 'nullable',
            'move_in_date' => 'nullable|date',
            'rental_type' => ['required', RmsConstants::validationRule(RmsConstants::RENTAL_TYPES)],
            'rental_duration' => 'required|integer|min:1',
            'paying_plan' => ['required', RmsConstants::validationRule(RmsConstants::PAYING_PLANS)],
            'base_rent_amount' => 'required_without:total_rental_amount|numeric|gt:0',
            'total_rental_amount' => 'required_without:base_rent_amount|numeric|gt:0',
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
            'tenant_full_name.required' => 'Please provide the tenant\'s full name.',
            'tenant_full_name.max' => 'The tenant name cannot exceed 150 characters.',
            'tenant_phone.required' => 'Please provide the tenant\'s phone number.',
            'tenant_phone.max' => 'The phone number cannot exceed 32 characters.',
            'tenant_email.email' => 'Please provide a valid email address.',
            'tenant_social_status.in' => 'Invalid social status. Valid options are: single, married, divorced, widowed, other.',
            'rental_type.required' => 'Please specify the rental type (monthly or annual).',
            'rental_type.in' => 'Rental type must be either "monthly" or "annual".',
            'rental_duration.required' => 'Please specify the rental duration.',
            'rental_duration.min' => 'Rental duration must be at least 1 period.',
            'paying_plan.required' => 'Please specify the payment plan.',
            'paying_plan.in' => 'Payment plan must be one of: monthly, quarterly, semi_annual, annual.',
            'base_rent_amount.required_without' => 'Please provide either the base or total rental amount.',
            'base_rent_amount.numeric' => 'The base rental amount must be a number.',
            'base_rent_amount.gt' => 'The base rental amount must be greater than zero.',
            'total_rental_amount.required_without' => 'Please provide either the total or base rental amount.',
            'total_rental_amount.numeric' => 'The rental amount must be a number.',
            'total_rental_amount.gt' => 'The rental amount must be greater than zero.',
            'currency.size' => 'Currency code must be exactly 3 characters (e.g., USD, SAR).',

            // Cost items validation messages
            'cost_items.array' => 'Cost items must be provided as an array.',
            'cost_items.*.name.required' => 'Each cost item must have a name.',
            'cost_items.*.name.max' => 'Cost item name cannot exceed 255 characters.',
            'cost_items.*.cost.required' => 'Each cost item must have a cost value.',
            'cost_items.*.cost.numeric' => 'Cost must be a number.',
            'cost_items.*.cost.min' => 'Cost cannot be negative.',
            'cost_items.*.type.required' => 'Please specify if the cost is fixed or percentage.',
            'cost_items.*.type.in' => 'Cost type must be either "fixed" or "percentage".',
            'cost_items.*.payer.required' => 'Please specify who pays this cost.',
            'cost_items.*.payer.in' => 'Payer must be either "owner" or "tenant".',
            'cost_items.*.payment_frequency.required' => 'Please specify the payment frequency.',
            'cost_items.*.payment_frequency.in' => 'Payment frequency must be "one_time" or "per_installment".',
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
            'tenant_job_title' => 'job title',
            'tenant_social_status' => 'social status',
            'tenant_national_id' => 'national ID',
            'unit_id' => 'unit',
            'project_id' => 'project',
            'building_id' => 'building',
            'move_in_date' => 'move-in date',
            'rental_type' => 'rental type',
            'rental_duration' => 'duration',
            'paying_plan' => 'payment plan',
            'base_rent_amount' => 'base rental amount',
            'total_rental_amount' => 'rental amount',
            'contract_number' => 'contract number',
        ];
    }
}

