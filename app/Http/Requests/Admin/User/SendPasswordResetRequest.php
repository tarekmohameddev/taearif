<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Send Password Reset Request
 *
 * Validates password reset code sending for user
 */
class SendPasswordResetRequest extends FormRequest
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
            'method' => ['required', 'in:email,whatsapp'],
            'country_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.required' => 'Method is required (email or whatsapp)',
            'method.in' => 'Method must be either email or whatsapp',
            'country_code.max' => 'Country code must not exceed 10 characters',
        ];
    }
}

