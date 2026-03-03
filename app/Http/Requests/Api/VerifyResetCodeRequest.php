<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyResetCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $apiRecaptchaEnabled = config('services.recaptcha.api_enabled', true);

        return [
            'code' => 'required|digits:6',
            'new_password' => 'required|min:8|confirmed',
            'recaptcha_token' => [
                Rule::requiredIf($apiRecaptchaEnabled),
                ...($apiRecaptchaEnabled ? [new \App\Rules\Recaptcha] : []),
            ],
        ];
    }
}
