<?php

namespace App\Http\Requests\Admin\Domain;

use Illuminate\Foundation\Http\FormRequest;

class StoreDomainRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'requested_domain' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i'],
            'current_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i'],
            'status' => ['nullable', 'boolean'],
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
            'user_id' => 'user',
            'requested_domain' => 'requested domain',
            'current_domain' => 'current domain',
            'status' => 'status',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'requested_domain.regex' => 'The requested domain must be a valid domain name (e.g., example.com)',
            'current_domain.regex' => 'The current domain must be a valid domain name (e.g., example.com)',
        ];
    }
}

