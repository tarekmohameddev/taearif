<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;

class SendWhatsAppMessageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'wa_number_id' => 'required|integer',
            'content' => 'required|string',
        ];
    }
}
