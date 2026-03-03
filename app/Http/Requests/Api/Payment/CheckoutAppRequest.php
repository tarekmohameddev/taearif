<?php

namespace App\Http\Requests\Api\Payment;

use App\Http\Requests\Api\BaseApiFormRequest;

class CheckoutAppRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'app_id' => 'required|exists:api_apps,id',
        ];
    }
}
