<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\OtpVerification;
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
        $otpRules = ['required', 'string', 'digits:5'];

        if ($this->allowsTestBypass()) {
            $bypassCode = (string) config('api.otp.registration.test_bypass_code', '');
            $bypassLength = strlen($bypassCode);

            if ($bypassLength > 0) {
                if ($bypassLength === 5) {
                    $otpRules = ['required', 'string', 'digits:5'];
                } else {
                    $otpRules = ['required', 'string', 'regex:/^(?:\d{5}|\d{' . $bypassLength . '})$/'];
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
        $phone = $this->input('phone');
        if (empty($phone)) {
            $user = auth('sanctum')->user();
            $phone = $user?->phone;
        }

        return OtpVerification::isTestBypassActive($phone);
    }
}
