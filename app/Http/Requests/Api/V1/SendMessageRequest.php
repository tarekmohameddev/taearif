<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Api\BaseApiFormRequest;

class SendMessageRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'conversation_id' => 'required|integer',
            'content' => 'required|string',
            'channel' => 'nullable|string|in:whatsapp',
        ];
    }
}
