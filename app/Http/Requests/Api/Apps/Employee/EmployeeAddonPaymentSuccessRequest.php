<?php

namespace App\Http\Requests\Api\Apps\Employee;

use App\Http\Requests\Api\BaseApiFormRequest;

class EmployeeAddonPaymentSuccessRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}
