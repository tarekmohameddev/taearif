<?php

namespace App\Http\Requests\Rms\Maintenance;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
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
            'rental_id' => 'required|integer|exists:rm_rentals,id',
            'category' => 'required|string|max:50',
            'priority' => ['required', RmsConstants::validationRule(RmsConstants::MAINTENANCE_PRIORITIES)],
            'title' => 'required|string|max:150',
            'description' => 'required|string',
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
            'rental_id.required' => 'Please select a rental property.',
            'rental_id.exists' => 'The selected rental property does not exist.',
            'category.required' => 'Please specify the maintenance category.',
            'priority.required' => 'Please specify the priority level.',
            'priority.in' => 'Priority must be one of: low, medium, high, critical.',
            'title.required' => 'Please provide a title for this maintenance ticket.',
            'title.max' => 'The title cannot exceed 150 characters.',
            'description.required' => 'Please provide a description of the maintenance issue.',
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
            'rental_id' => 'rental property',
            'payer_share_percent' => 'payer share percentage',
            'scheduled_date' => 'scheduled date',
            'assigned_to_vendor_id' => 'assigned vendor',
        ];
    }
}

