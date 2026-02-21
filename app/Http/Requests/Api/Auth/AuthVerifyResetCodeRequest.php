<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;

class AuthVerifyResetCodeRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'identifier' => 'required|string',
            'code' => 'required|digits:6',
            'new_password' => 'required|min:8|confirmed',
        ];
    }
}
