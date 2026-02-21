<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'identifier' => 'required',
            'method' => 'required|in:email,phone',
            'country_code' => 'nullable|string|max:10',
            'recaptcha_token' => ['required', new \App\Rules\Recaptcha],
        ];
    }
}
