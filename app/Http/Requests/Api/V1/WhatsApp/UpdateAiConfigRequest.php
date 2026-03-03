<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateAiConfigRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'enabled' => 'nullable|boolean',
            'business_hours_only' => 'nullable|boolean',
            'business_hours_start' => 'nullable|date_format:H:i',
            'business_hours_end' => 'nullable|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            'scenarios' => 'nullable|array',
            'tone' => 'nullable|string|max:20',
            'language' => 'nullable|string|max:10',
            'custom_instructions' => 'nullable|string',
            'fallback_to_human' => 'nullable|boolean',
            'fallback_delay' => 'nullable|integer|min:0',
        ];
    }
}
