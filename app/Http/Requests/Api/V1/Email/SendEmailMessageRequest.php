<?php

namespace App\Http\Requests\Api\V1\Email;

use App\Http\Requests\Api\BaseApiFormRequest;

class SendEmailMessageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'recipient_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (trim((string) $this->header('Idempotency-Key', '')) === '') {
                $validator->errors()->add('Idempotency-Key', 'Idempotency-Key header is required.');
            }
        });
    }
}
