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
        /** @var int|null $employeeId */
        $employeeId = (int) optional(app('router')->current())->parameter('employee');

        return [
            'username' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
                Rule::unique('admins', 'username')->ignore($employeeId, 'id'),
            ],
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:255',
                Rule::unique('admins', 'email')->ignore($employeeId, 'id'),
            ],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role_id' => ['sometimes', 'nullable', 'integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'nullable', 'array'],
            'permissions.*' => ['string', 'max:255'],
            'status' => ['sometimes', 'boolean'],
            'last_login_at' => ['sometimes', 'nullable', 'date'],
            'image' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    // If it's a file upload, validate as image
                    if (request()->hasFile('image')) {
                        $file = request()->file('image');
                        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png'];
                        $maxSize = 2048; // 2MB in KB
                        
                        if (!in_array($file->getMimeType(), $allowedMimes)) {
                            $fail('The image must be a file of type: jpeg, jpg, png.');
                        }
                        
                        if ($file->getSize() > $maxSize * 1024) {
                            $fail('The image must not be larger than ' . $maxSize . ' kilobytes.');
                        }
                    } 
                    // If it's a string, validate it's a valid filename
                    elseif (is_string($value) && !empty($value)) {
                        if (!preg_match('/^[a-zA-Z0-9._-]+\.(jpg|jpeg|png)$/i', $value)) {
                            $fail('The image path must be a valid image filename (jpg, jpeg, or png).');
                        }
                    }
                },
            ],
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

