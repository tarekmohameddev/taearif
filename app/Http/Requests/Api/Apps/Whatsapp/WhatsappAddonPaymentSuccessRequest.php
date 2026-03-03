<?php

namespace App\Http\Requests\Api\Apps\Whatsapp;

use App\Http\Requests\Api\BaseApiFormRequest;

class WhatsappAddonPaymentSuccessRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'paymentId' => 'nullable|string',
            'trandata' => 'nullable|string',
        ];
    }
}
