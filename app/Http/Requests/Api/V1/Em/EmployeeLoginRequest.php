<?php

namespace App\Http\Requests\Api\V1\Em;

use App\Http\Requests\Api\BaseApiFormRequest;

class EmployeeLoginRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string',
        ];
    }
}
