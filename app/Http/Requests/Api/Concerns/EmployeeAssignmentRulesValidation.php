<?php

namespace App\Http\Requests\Api\Concerns;

use Illuminate\Validation\Rule;

trait EmployeeAssignmentRulesValidation
{
    /**
     * Rules for optional `employeeRules` payload (same shape as Customers Hub save rules).
     *
     * @param  string|null  $expectedEmployeeId  If null (create), employeeId on the block is optional and ignored; max one block.
     * @return array<string, mixed>
     */
    protected function employeeAssignmentRulesValidationRules(?string $expectedEmployeeId): array
    {
        $rules = [
            'employeeRules' => ['sometimes', 'nullable', 'array', 'max:1'],
            'employeeRules.*.isActive' => ['required', 'boolean'],
            'employeeRules.*.rules' => ['present', 'array'],
            'employeeRules.*.rules.*.id' => ['nullable', 'string'],
            'employeeRules.*.rules.*.field' => ['required', 'in:budgetMin,budgetMax,propertyType,city,source'],
            'employeeRules.*.rules.*.operator' => ['required', 'in:equals,greaterThan,lessThan,contains'],
            'employeeRules.*.rules.*.value' => ['required', 'string'],
        ];

        if ($expectedEmployeeId === null) {
            $rules['employeeRules.*.employeeId'] = ['nullable', 'string'];
        } else {
            $rules['employeeRules.*.employeeId'] = [
                'required',
                'string',
                Rule::in([(string) $expectedEmployeeId]),
            ];
        }

        return $rules;
    }
}
