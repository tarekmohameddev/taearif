<?php

namespace App\Http\Requests\Api\V1\Em;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class EmployeeRegisterRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:50',
            'active' => 'nullable|boolean',
        ];
    }
}
