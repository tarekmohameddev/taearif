<?php

namespace App\Http\Requests\Rms\Installment;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallmentRequest extends FormRequest
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
            'status' => ['sometimes', RmsConstants::validationRule(RmsConstants::INSTALLMENT_STATUSES)],
            'paid_amount' => 'nullable|numeric|min:0',
            'paid_at' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
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
            'status.in' => 'The selected status is invalid. Valid options are: pending, paid, partial, overdue, void.',
            'paid_amount.numeric' => 'The paid amount must be a number.',
            'paid_amount.min' => 'The paid amount cannot be negative.',
            'paid_at.date' => 'The payment date must be a valid date.',
            'reference.max' => 'The reference number cannot exceed 100 characters.',
            'notes.max' => 'The notes cannot exceed 255 characters.',
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
            'paid_amount' => 'paid amount',
            'paid_at' => 'payment date',
            'reference' => 'reference number',
        ];
    }
}

