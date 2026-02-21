<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\Recaptcha;

class LoginApiRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'recaptcha_token' => ['required', new Recaptcha],
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'recaptcha_token.required' => 'reCAPTCHA token is required',
            'email.required' => 'Email is required',
            'password.required' => 'Password is required',
        ];
    }
}
