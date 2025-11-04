<?php

namespace App\Http\Requests\Rms\Rental;

use App\Constants\RmsConstants;
use Illuminate\Foundation\Http\FormRequest;

class CollectPaymentRequest extends FormRequest
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
            // Auto-selection fields (optional, mutually exclusive with manual payments)
            'auto_select' => 'nullable|boolean',
            'auto_select_amount' => 'required_if:auto_select,true|numeric|min:0.01',
            'auto_select_strategy' => 'nullable|in:overdue_first,oldest_first,sequential',

            // Fallback amount field (when payments is empty, use this for auto-select)
            'amount' => 'nullable|numeric|min:0.01',
            'payment-amount' => 'nullable|numeric|min:0.01',  // Alternative field name

            // Manual payment fields (required unless auto_select is true OR amount is provided)
            'payments' => 'required_unless:auto_select,true|array',
            'payments.*.installment_id' => 'required_without:auto_select|exists:rm_payment_installments,id',
            'payments.*.payment_type' => [
                'required_without:auto_select',
                RmsConstants::validationRule(RmsConstants::PAYMENT_TYPES)
            ],
            'payments.*.cost_item_id' => 'required_if:payments.*.payment_type,cost_item|exists:rental_cost_items,id',
            'payments.*.amount' => 'required_without:auto_select|numeric|min:0.01',
            'payments.*.notes' => 'nullable|string|max:255',

            // Common payment fields (always required)
            'payment_method' => ['required', RmsConstants::validationRule(RmsConstants::PAYMENT_METHODS)],
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:100',
            'receipt_image_path' => 'nullable|string|max:500',
            'transfer_to' => ['required', RmsConstants::validationRule(RmsConstants::TRANSFER_TO_OPTIONS)],
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
            // Auto-selection messages
            'auto_select.boolean' => 'Auto-select must be true or false.',
            'auto_select_amount.required_if' => 'Payment amount is required when auto-select is enabled.',
            'auto_select_amount.numeric' => 'Payment amount must be a number.',
            'auto_select_amount.min' => 'Payment amount must be at least 0.01.',
            'auto_select_strategy.in' => 'Invalid auto-select strategy. Choose from: overdue_first, oldest_first, sequential.',

            // Manual payment messages
            'payments.required_unless' => 'Please provide at least one payment or enable auto-select.',
            'payments.array' => 'Payments must be provided as an array.',
            'payments.min' => 'At least one payment is required.',
            'payments.*.installment_id.required_without' => 'Each payment must have an installment ID.',
            'payments.*.installment_id.exists' => 'The selected installment does not exist.',
            'payments.*.payment_type.required_without' => 'Please specify the payment type.',
            'payments.*.payment_type.in' => 'Payment type must be rent, cost_item, or deposit.',
            'payments.*.cost_item_id.required_if' => 'Cost item ID is required when payment type is cost_item.',
            'payments.*.cost_item_id.exists' => 'The selected cost item does not exist.',
            'payments.*.amount.required_without' => 'Payment amount is required.',
            'payments.*.amount.numeric' => 'Payment amount must be a number.',
            'payments.*.amount.min' => 'Payment amount must be at least 0.01.',
            'payments.*.notes.max' => 'Payment notes cannot exceed 255 characters.',

            // Common messages
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Invalid payment method selected.',
            'payment_date.date' => 'Payment date must be a valid date.',
            'reference.max' => 'Reference number cannot exceed 100 characters.',
            'notes.max' => 'Notes cannot exceed 255 characters.',
            'bank_name.max' => 'Bank name cannot exceed 100 characters.',
            'receipt_image_path.max' => 'Receipt image path is too long.',
            'transfer_to.required' => 'Please specify the transfer destination.',
            'transfer_to.in' => 'Invalid transfer destination selected.',
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
            'payment_method' => 'payment method',
            'payment_date' => 'payment date',
            'bank_name' => 'bank name',
            'transfer_to' => 'transfer destination',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $paymentMethod = $this['payment_method'] ?? null;
        $bankName = $this['bank_name'] ?? null;
        $autoSelect = $this['auto_select'] ?? false;
        $payments = $this['payments'] ?? [];
        $amount = $this['amount'] ?? null;
        $autoSelectAmount = $this['auto_select_amount'] ?? null;

        $validator->after(function ($validator) use ($paymentMethod, $bankName, $autoSelect, $payments) {
            // Custom validation: bank_name required for bank transfers
            if ($paymentMethod === RmsConstants::PAYMENT_METHOD_BANK_TRANSFER && empty($bankName)) {
                $validator->errors()->add(
                    'bank_name',
                    'Bank name is required when payment method is bank transfer.'
                );
            }

            // Custom validation: Cannot have both manual payments and auto_select
            if ($autoSelect && !empty($payments)) {
                $validator->errors()->add(
                    'auto_select',
                    'Cannot use both auto-select and manual payment selection. Please choose one method.'
                );
            }

            // NOTE: Empty payments array is now VALID - will auto-pay all outstanding installments
            // This is handled in the controller by auto-detecting and calculating total outstanding
        });
    }
}

