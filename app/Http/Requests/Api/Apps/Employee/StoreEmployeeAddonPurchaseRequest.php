<?php

namespace App\Http\Requests\Api\Apps\Employee;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreEmployeeAddonPurchaseRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'qty' => ['required', 'integer', 'min:1'],
            'plan_id' => ['required', 'exists:employee_addon_plans,id'],
        ];
    }
}
