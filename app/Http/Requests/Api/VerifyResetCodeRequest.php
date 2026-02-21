<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class VerifyResetCodeRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'code' => 'required|digits:6',
            'new_password' => 'required|min:8|confirmed',
            'recaptcha_token' => ['required', new \App\Rules\Recaptcha],
        ];
    }
}
