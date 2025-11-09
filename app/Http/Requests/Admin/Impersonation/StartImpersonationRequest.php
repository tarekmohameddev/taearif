<?php

namespace App\Http\Requests\Admin\Impersonation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Start Impersonation Request
 *
 * Validates data for starting an impersonation session
 */
class StartImpersonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled by Gate in routes
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
            'reason' => ['nullable', 'string', 'max:255'],
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
            'reason.max' => 'The reason cannot exceed 255 characters',
        ];
    }
}

