<?php

namespace App\Http\Requests\Api\Isthara;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreIstharaRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|regex:/^05[0-9]{8}$/',
            'recaptcha_token' => 'required',
        ];
    }
}
