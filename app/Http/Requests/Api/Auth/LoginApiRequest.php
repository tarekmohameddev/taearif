<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\Recaptcha;
use Illuminate\Validation\Rule;

class LoginApiRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $apiRecaptchaEnabled = config('services.recaptcha.api_enabled', true);

        return [
            'recaptcha_token' => [
                Rule::requiredIf($apiRecaptchaEnabled),
                ...($apiRecaptchaEnabled ? [new Recaptcha] : []),
            ],
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
