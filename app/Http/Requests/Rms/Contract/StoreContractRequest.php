<?php

namespace App\Http\Requests\Rms\Contract;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractRequest extends FormRequest
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => ['required', RmsConstants::validationRule([
                RmsConstants::CONTRACT_STATUS_PENDING,
                RmsConstants::CONTRACT_STATUS_ACTIVE
            ])],
            'file_path' => 'nullable|string|max:255',
            'generate_schedule' => 'nullable|boolean',
            'property_id' => 'nullable|integer|min:1',
            'project_id' => 'nullable|integer|min:1',
            'property_name' => 'nullable|string|max:150',
            'project_name' => 'nullable|string|max:150',
            'grace_period_months' => 'nullable|integer|min:0|max:2',
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
            'start_date.required' => 'Please provide a start date for the contract.',
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.required' => 'Please provide an end date for the contract.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
            'status.required' => 'Please specify the contract status.',
            'status.in' => 'For new contracts, status must be either "pending" or "active".',
            'property_id.min' => 'Invalid property ID.',
            'project_id.min' => 'Invalid project ID.',
            'property_name.max' => 'The property name cannot exceed 150 characters.',
            'project_name.max' => 'The project name cannot exceed 150 characters.',
            'grace_period_months.min' => 'Grace period cannot be negative.',
            'grace_period_months.max' => 'Grace period cannot exceed 2 months.',
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
            'start_date' => 'start date',
            'end_date' => 'end date',
            'file_path' => 'contract file',
            'generate_schedule' => 'generate payment schedule',
            'property_id' => 'property',
            'project_id' => 'project',
            'property_name' => 'property name',
            'project_name' => 'project name',
            'grace_period_months' => 'grace period',
        ];
    }
}

