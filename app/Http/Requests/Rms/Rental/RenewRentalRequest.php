<?php

namespace App\Http\Requests\Rms\Rental;

use App\Constants\RmsConstants;
use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\WholeNumber;

class RenewRentalRequest extends BaseApiFormRequest
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
            'rental_type' => ['required', RmsConstants::validationRule(RmsConstants::RENTAL_TYPES)],
            'rental_duration' => 'required|integer|min:1',
            'paying_plan' => ['required', RmsConstants::validationRule(RmsConstants::PAYING_PLANS)],
            'total_rental_amount' => ['required', 'numeric', 'gt:0', new WholeNumber],
            'currency' => 'nullable|string|size:3',
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
            'rental_type.required' => 'Please specify the rental type for renewal.',
            'rental_type.in' => 'Rental type must be either "monthly" or "annual".',
            'rental_duration.required' => 'Please specify the renewal duration.',
            'rental_duration.min' => 'Rental duration must be at least 1 period.',
            'paying_plan.required' => 'Please specify the payment plan for renewal.',
            'paying_plan.in' => 'Payment plan must be one of: monthly, quarterly, semi_annual, annual.',
            'total_rental_amount.required' => 'Please provide the total rental amount.',
            'total_rental_amount.numeric' => 'The rental amount must be a number.',
            'total_rental_amount.gt' => 'The rental amount must be greater than zero.',
            'currency.size' => 'Currency code must be exactly 3 characters.',
            'cost_items.*.name.required' => 'Each cost item must have a name.',
            'cost_items.*.cost.required' => 'Each cost item must have a cost value.',
            'cost_items.*.type.in' => 'Cost type must be either "fixed" or "percentage".',
            'cost_items.*.payer.in' => 'Payer must be either "owner" or "tenant".',
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
            'rental_type' => 'rental type',
            'rental_duration' => 'duration',
            'paying_plan' => 'payment plan',
            'total_rental_amount' => 'rental amount',
        ];
    }
}

