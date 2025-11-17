<?php

namespace App\Http\Requests\Admin\Affiliate;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAffiliateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'fullname' => ['sometimes', 'required', 'string', 'max:255'],
            'bank_name' => ['sometimes', 'required', 'string', 'max:255'],
            'bank_account_number' => ['sometimes', 'required', 'string', 'max:255'],
            'iban' => ['sometimes', 'required', 'string', 'max:255'],
            'commission_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'request_status' => ['sometimes', 'required', 'in:pending,approved,rejected'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'fullname' => 'full name',
            'bank_name' => 'bank name',
            'bank_account_number' => 'bank account number',
            'iban' => 'IBAN',
            'commission_percentage' => 'commission percentage',
            'request_status' => 'request status',
        ];
    }
}

