<?php

namespace App\Http\Requests\OwnerRental;

use App\Http\Requests\Api\BaseApiFormRequest;

class ForgotPasswordRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'email' => 'required|email',
        ];
    }
}
