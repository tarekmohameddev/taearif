<?php

namespace App\Http\Requests\Api\Payment;

use App\Http\Requests\Api\BaseApiFormRequest;

class CheckoutCreditsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'credits' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:arb,myfatoorah,test',
        ];
    }
}
