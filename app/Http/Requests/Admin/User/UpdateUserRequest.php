<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update User Request
 *
 * Validates user update data
 */
class UpdateUserRequest extends FormRequest
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
        // Retrieve the user id from the route parameter named 'user'
        $userId = request()->route('user'); // user ID

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(function ($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
            ],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->where(function ($query) use ($userId) {
                    return $query->where('id', '!=', $userId);
                })
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'domain' => ['nullable', 'string', 'max:50'],
            // Accept id or name; normalize in service
            'city'     => ['nullable'],
            'district' => ['nullable'],
            'address' => ['nullable', 'string', 'max:500'],
            // country no longer used for this flow
            'industry_type' => ['nullable', 'string', 'max:100'],
            'company_size' => ['nullable', 'string', 'max:50'],
            'package_id' => ['nullable','integer','exists:packages,id'],
            'plan_change_type' => ['nullable','in:immediate,scheduled'],
            'active' => ['sometimes', 'boolean'],
            'featured' => ['sometimes', 'integer', 'in:0,1'],
            'email_verified' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'integer'],
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
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'username.unique' => 'This username is already taken',
        ];
    }
}

