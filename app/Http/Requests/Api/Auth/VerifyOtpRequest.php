<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class VerifyOtpRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $hasBearer = !empty(request()->bearerToken());
        $otpRules = ['required', 'string', 'digits:6'];

        if ($this->allowsTestBypass()) {
            $bypassCode = (string) config('api.otp.registration.test_bypass_code', '');
            $bypassLength = strlen($bypassCode);

            if ($bypassLength > 0) {
                if ($bypassLength === 6) {
                    $otpRules = ['required', 'string', 'digits:6'];
                } else {
                    $otpRules = ['required', 'string', 'regex:/^(?:\d{6}|\d{' . $bypassLength . '})$/'];
                }
            }
        }

        return [
            'otp' => $otpRules,
            'phone' => [
                'nullable',
                'string',
                Rule::requiredIf(!$hasBearer),
            ],
        ];
    }

    private function allowsTestBypass(): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        return (bool) config('api.otp.registration.test_bypass_enabled', false);
    }
}
