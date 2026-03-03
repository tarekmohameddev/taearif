<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $apiRecaptchaEnabled = config('services.recaptcha.api_enabled', true);

        return [
            'identifier' => 'required',
            'method' => 'required|in:email,phone',
            'country_code' => 'nullable|string|max:10',
            'recaptcha_token' => [
                Rule::requiredIf($apiRecaptchaEnabled),
                ...($apiRecaptchaEnabled ? [new \App\Rules\Recaptcha] : []),
            ],
        ];
    }
}
