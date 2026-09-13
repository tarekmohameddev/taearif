<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Api\Concerns\EmployeeAssignmentRulesValidation;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends BaseApiFormRequest
{
    use EmployeeAssignmentRulesValidation;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $routeEmployee = $this->route('employee');
        $employeeId = $routeEmployee instanceof \Illuminate\Database\Eloquent\Model
            ? $routeEmployee->getKey()
            : $routeEmployee;

        return array_merge([
            'first_name' => ['nullable', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore($employeeId)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6'],
            'active' => ['boolean'],
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:api_roles,id'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ], $this->employeeAssignmentRulesValidationRules((string) $employeeId));
    }
}

