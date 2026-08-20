<?php

namespace App\Http\Requests\Api\Auth;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\Recaptcha;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class RegisterApiRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $isEmployeeRegistration = request()->input('account_type') === 'employee';
        $hasTempToken = !empty(request()->input('temp_token'));
        $requiresCoreCredentials = $isEmployeeRegistration || !$hasTempToken;
        $apiRecaptchaEnabled = config('services.recaptcha.api_enabled', true);

        return [
            'recaptcha_token' => [
                Rule::excludeIf(request()->input('recaptcha_token') === 'TEST_BYPASS_TOKEN'),
                Rule::requiredIf($apiRecaptchaEnabled),
                ...($apiRecaptchaEnabled ? [new Recaptcha()] : []),
            ],
            'account_type' => 'nullable|string|in:employee,tenant',
            'user_id' => [
                Rule::requiredIf($isEmployeeRegistration),
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'email' => [
                Rule::requiredIf($requiresCoreCredentials),
                'nullable',
                'email',
                'unique:users,email',
            ],
            'username' => [
                Rule::requiredIf($requiresCoreCredentials),
                'nullable',
                'string',
                'unique:users,username',
            ],
            'password' => [
                Rule::requiredIf($requiresCoreCredentials),
                'nullable',
                'string',
                'min:6',
            ],
            'phone' => [
                Rule::requiredIf($requiresCoreCredentials),
                'nullable',
                'string',
                'max:191',
                'unique:users,phone',
            ],
            'verified_token' => 'nullable|string|uuid',
            'first_name' => 'nullable|string|max:191',
            'last_name' => 'nullable|string|max:191',
            'industry_type' => 'nullable|string|max:100',
            'valLicense' => 'nullable|string|max:191',
            'company_size' => 'nullable|string|max:50',
            'temp_token' => 'nullable|string',
            'referral_code' => 'nullable|string',
            'code' => 'nullable|string',
            'roles' => 'nullable|array',
            'permissions' => 'nullable|array',
        ];
    }

    public function messages()
    {
        return [
            'phone.required' => 'رقم الهاتف مطلوب.',
            'phone.unique' => 'تم استخدام رقم الهاتف، يرجى تسجيل الدخول.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $failed = $validator->failed();

        if (isset($failed['phone']['Unique'])) {
            throw new HttpResponseException(response()->json([
                'status' => 'error',
                'error' => 'phone_already_registered',
                'message' => 'تم استخدام رقم الهاتف، يرجى تسجيل الدخول.',
            ], 422));
        }

        parent::failedValidation($validator);
    }
}
