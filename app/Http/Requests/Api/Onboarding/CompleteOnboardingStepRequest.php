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
            'step' => 'required|in:site_identity,contact_info,first_property,integrated_link,connect_site,properties',
        ];
    }
}
