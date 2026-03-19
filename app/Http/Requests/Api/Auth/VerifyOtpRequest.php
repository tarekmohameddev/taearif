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

        return [
            'otp' => ['required', 'string', 'digits:6'],
            'phone' => [
                'nullable',
                'string',
                Rule::requiredIf(!$hasBearer),
            ],
        ];
    }
}
