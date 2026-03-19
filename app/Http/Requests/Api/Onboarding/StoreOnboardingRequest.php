<?php

namespace App\Http\Requests\Api\Onboarding;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreOnboardingRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'colors' => 'required|array',
            'colors.primary' => 'required|string|max:7',
            'colors.secondary' => 'required|string|max:7',
            'colors.accent' => 'required|string|max:7',
            'logo' => 'nullable|string',
            'favicon' => 'nullable|string',
            'valLicense' => 'nullable|string',
            'workingHours' => 'nullable|string',
            'address' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'allow_update' => 'nullable|boolean',
        ];
    }
}
