<?php

namespace App\Http\Requests\Rms\Rental;

use App\Http\Requests\Api\BaseApiFormRequest;

class EndContractRequest extends BaseApiFormRequest
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
            'end_date' => 'required|date|after_or_equal:today',
            'termination_reason' => 'nullable|string|max:255',
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
            'end_date.required' => 'Please provide the contract end date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date cannot be in the past.',
            'termination_reason.max' => 'The termination reason cannot exceed 255 characters.',
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

