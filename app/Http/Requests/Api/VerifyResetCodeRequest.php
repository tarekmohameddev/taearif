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

        $codeRules = ['required', 'string', 'digits:6'];
        if ($this->allowsPasswordResetTestBypass()) {
            $bypassCode = (string) config('api.password_reset.email_test_bypass_code', '');
            $bypassLength = strlen($bypassCode);
            if ($bypassLength > 0) {
                if ($bypassLength === 6) {
                    $codeRules = ['required', 'string', 'digits:6'];
                } else {
                    $codeRules = ['required', 'string', 'regex:/^(?:\d{6}|\d{' . $bypassLength . '})$/'];
                }
            }
        }

        return [
            'code' => $codeRules,
            'new_password' => 'required|min:8|confirmed',
            'recaptcha_token' => [
                Rule::requiredIf($apiRecaptchaEnabled),
                ...($apiRecaptchaEnabled ? [new \App\Rules\Recaptcha] : []),
            ],
        ];
    }

    private function allowsPasswordResetTestBypass(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        return (bool) config('api.password_reset.email_test_bypass_enabled', false);
    }
}
