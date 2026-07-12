<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateUserProfileRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'first_name' => 'sometimes|nullable|string|max:191',
            'last_name' => 'sometimes|nullable|string|max:191',
            'name' => 'sometimes|nullable|string|max:191',
            'email' => [
                'sometimes',
                'nullable',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:191',
                Rule::unique('users', 'phone')->ignore($userId),
            ],
            'address' => 'sometimes|nullable|string|max:500',
            'city' => 'sometimes|nullable|string|max:191',
            'state' => 'sometimes|nullable|string|max:191',
            'district' => 'sometimes|nullable|string|max:191',
            'country' => 'sometimes|nullable|string|max:191',
            'company_name' => 'sometimes|nullable|string|max:100',
            'company_email' => 'sometimes|nullable|email|max:100',
            'company_phone' => 'sometimes|nullable|string|max:255',
            'company_address' => 'sometimes|nullable|string|max:255',
            'working_hours' => 'sometimes|nullable|string|max:100',
            'current_password' => 'required_with:password|nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
        ];
    }
}
