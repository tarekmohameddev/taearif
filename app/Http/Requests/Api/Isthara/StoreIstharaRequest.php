<?php

namespace App\Http\Requests\Api\Isthara;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\Recaptcha;
use Illuminate\Validation\Rule;

class StoreIstharaRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $apiRecaptchaEnabled = config('services.recaptcha.api_enabled', true);

        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|regex:/^05[0-9]{8}$/',
            'recaptcha_token' => [
                Rule::requiredIf($apiRecaptchaEnabled),
                ...($apiRecaptchaEnabled ? [new Recaptcha] : []),
            ],
        ];
    }
}
