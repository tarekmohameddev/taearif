<?php

namespace App\Http\Requests\Admin\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Update Employee Request
 *
 * Validates employee update data
 */
class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        /** @var string|null $employeeUuid */
        $employeeUuid = optional(app('router')->current())->parameter('employee');

        return [
            'username' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('admins', 'username')->ignore($employeeUuid, 'uuid'),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($employeeUuid, 'uuid'),
            ],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'status' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png', 'max:2048'],
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
            'username.unique' => 'This username is already taken',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already registered',
            'role_id.exists' => 'Selected role does not exist',
        ];
    }
}

