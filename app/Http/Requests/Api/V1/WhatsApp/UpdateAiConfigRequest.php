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
            'business_hours' => 'nullable|array',
            'business_hours.*.open' => 'nullable|boolean',
            'business_hours.*.from' => 'nullable|date_format:H:i',
            'business_hours.*.to' => 'nullable|date_format:H:i',
            'timezone' => 'nullable|string|max:50',
            'scenarios' => 'nullable|array',
            'tone' => 'nullable|string|max:20',
            'language' => 'nullable|string|max:10',
            'custom_instructions' => 'nullable|string',
            'goal' => 'nullable|string|in:salesman,support,booking',
            'autonomy_level' => 'nullable|string|in:off,shadow,autonomous',
            'reply_length_target' => 'nullable|integer|min:50|max:2000',
            'confidence_threshold' => 'nullable|integer|min:0|max:100',
            'groundedness_threshold' => 'nullable|integer|min:0|max:100',
            'escalation_rules' => 'nullable|array',
            'disclose_as_assistant' => 'nullable|boolean',
            'assistant_name' => 'nullable|string|max:100',
            'monthly_token_budget' => 'nullable|integer|min:0',
            'fallback_to_human' => 'nullable|boolean',
            'fallback_delay' => 'nullable|integer|min:0',
        ];
    }
}
