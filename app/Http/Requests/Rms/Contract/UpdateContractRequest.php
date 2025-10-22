<?php

namespace App\Http\Requests\Rms\Contract;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
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
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => ['sometimes', RmsConstants::validationRule(RmsConstants::CONTRACT_STATUSES)],
            'file_path' => 'sometimes|string|max:255',
            'property_id' => 'sometimes|nullable|integer|min:1',
            'project_id' => 'sometimes|nullable|integer|min:1',
            'property_name' => 'sometimes|nullable|string|max:150',
            'project_name' => 'sometimes|nullable|string|max:150',
            'grace_period_months' => 'sometimes|nullable|integer|min:0|max:2',
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
            'start_date.date' => 'The start date must be a valid date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after' => 'The end date must be after the start date.',
            'status.in' => 'Invalid contract status. Valid options are: pending, active, expired, terminated.',
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
            'property_id' => 'property',
            'project_id' => 'project',
            'property_name' => 'property name',
            'project_name' => 'project name',
            'grace_period_months' => 'grace period',
        ];
    }
}

