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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $key = request()->header('Idempotency-Key');
            if ($key === null || trim((string) $key) === '') {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key header is required.');
            }
        });
    }
}
