<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Api\Concerns\EmployeeAssignmentRulesValidation;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends BaseApiFormRequest
{
    use EmployeeAssignmentRulesValidation;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return array_merge([
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:6'],
            'active' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:api_roles,id'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ], $this->employeeAssignmentRulesValidationRules(null));
    }
}
