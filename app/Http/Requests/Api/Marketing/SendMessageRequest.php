<?php

namespace App\Http\Requests\Api\Marketing;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'to' => 'required|string|max:50',
            'message' => 'required|string|max:1000',
            'message_type' => 'sometimes|string|in:text,media,template',
            'media_url' => 'nullable|url',
        ];
    }
}
