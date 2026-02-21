<?php

namespace App\Http\Requests\Api\Apps\Whatsapp;

use Illuminate\Foundation\Http\FormRequest;

class ChatRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'message' => 'required|string',
            'user_id' => 'required|integer',
            'whatsapp_number' => 'required|string',
        ];
    }
}
