<?php

namespace App\Http\Requests\Api\Marketing;

use App\Http\Requests\Api\BaseApiFormRequest;

class PurchaseCreditPackageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'package_id' => 'required|exists:credit_packages,id',
            'payment_method' => 'required|string',
        ];
    }
}
