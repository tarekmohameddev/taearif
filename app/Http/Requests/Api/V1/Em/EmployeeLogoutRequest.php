<?php

namespace App\Http\Requests\Api\V1\Em;

use App\Http\Requests\Api\BaseApiFormRequest;

class EmployeeLogoutRequest extends BaseApiFormRequest
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
