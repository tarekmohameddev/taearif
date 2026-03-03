<?php

namespace App\Http\Requests\Api\Theme;

use App\Http\Requests\Api\BaseApiFormRequest;

class ThemePaymentSuccessRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [];
    }
}
