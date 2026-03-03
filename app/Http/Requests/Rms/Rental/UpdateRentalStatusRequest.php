<?php

namespace App\Http\Requests\Rms\Rental;

use App\Constants\RmsConstants;
use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateRentalStatusRequest extends BaseApiFormRequest
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
            'status' => ['required', 'string', RmsConstants::validationRule(RmsConstants::RENTAL_STATUSES)],
            'end_date' => 'nullable|date',
            'termination_reason' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:500',
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
            'status.required' => 'Please specify the new status.',
            'status.in' => 'Invalid status. Valid options are: active, inactive, terminated, ended, cancelled, draft.',
            'end_date.date' => 'The end date must be a valid date.',
            'termination_reason.max' => 'The termination reason cannot exceed 500 characters.',
            'notes.max' => 'The notes cannot exceed 500 characters.',
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
            'end_date' => 'end date',
            'termination_reason' => 'termination reason',
        ];
    }
}

