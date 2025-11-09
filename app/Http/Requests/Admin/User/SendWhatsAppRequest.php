<?php

namespace App\Http\Requests\Admin\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Send WhatsApp Request
 */
class SendWhatsAppRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:4096'],
            'template_name' => ['nullable', 'string', 'max:150'],
            'template_variables' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Message content is required',
            'message.max' => 'Message may not be greater than 4096 characters',
        ];
    }
}

