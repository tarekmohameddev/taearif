<?php

namespace App\Http\Requests\Api\Affiliate;

use App\Http\Requests\Api\BaseApiFormRequest;

class AffiliateRegisterRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fullname' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:30',
            'iban' => 'required|string|max:34',
        ];
    }
}
