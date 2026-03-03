<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class SaveRulesRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'employeeRules' => 'required|array|min:1',
            'employeeRules.*.employeeId' => 'required|string',
            'employeeRules.*.isActive' => 'required|boolean',
            'employeeRules.*.rules' => 'required|array',
            'employeeRules.*.rules.*.id' => 'nullable|string',
            'employeeRules.*.rules.*.field' => 'required|in:budgetMin,budgetMax,propertyType,city,source',
            'employeeRules.*.rules.*.operator' => 'required|in:equals,greaterThan,lessThan,contains',
            'employeeRules.*.rules.*.value' => 'required|string',
        ];
    }
}
