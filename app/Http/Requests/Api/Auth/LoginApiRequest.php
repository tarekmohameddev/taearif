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
            'email' => 'required_without:phone|nullable|email',
            'phone' => 'required_without:email|nullable|string',
            'password' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'recaptcha_token.required' => 'reCAPTCHA token is required',
            'email.required_without' => 'Email or phone is required',
            'email.email' => 'Email must be a valid email address',
            'phone.required_without' => 'Email or phone is required',
            'password.required' => 'Password is required',
        ];
    }
}
