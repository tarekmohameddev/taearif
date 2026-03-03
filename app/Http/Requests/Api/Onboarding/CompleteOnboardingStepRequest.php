<?php

namespace App\Http\Requests\Api\Onboarding;

use App\Http\Requests\Api\BaseApiFormRequest;

class CompleteOnboardingStepRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'step' => 'required|in:banner,footer,homepage_about_update,menu_builder,projects,properties',
        ];
    }
}
