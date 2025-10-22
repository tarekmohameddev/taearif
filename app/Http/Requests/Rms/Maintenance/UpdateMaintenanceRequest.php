<?php

namespace App\Http\Requests\Rms\Maintenance;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMaintenanceRequest extends FormRequest
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
            'title' => 'sometimes|string|max:150',
            'description' => 'sometimes|string',
            'estimated_cost' => 'nullable|numeric',
            'payer' => ['nullable', RmsConstants::validationRule(RmsConstants::MAINTENANCE_PAYERS)],
            'payer_share_percent' => 'nullable|integer|min:0|max:100',
            'scheduled_date' => 'nullable|date',
            'assigned_to_vendor_id' => 'nullable|integer',
            'notes' => 'nullable|string',
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
            'title.max' => 'The title cannot exceed 150 characters.',
            'estimated_cost.numeric' => 'The estimated cost must be a number.',
            'payer.in' => 'Payer must be one of: landlord, tenant, shared.',
            'payer_share_percent.min' => 'The share percentage cannot be negative.',
            'payer_share_percent.max' => 'The share percentage cannot exceed 100%.',
            'scheduled_date.date' => 'The scheduled date must be a valid date.',
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
            'payer_share_percent' => 'payer share percentage',
            'scheduled_date' => 'scheduled date',
            'assigned_to_vendor_id' => 'assigned vendor',
        ];
    }
}

