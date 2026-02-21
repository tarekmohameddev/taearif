<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreAutomationRuleRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'wa_number_id' => 'nullable|integer',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'trigger' => 'required|string|in:new_inquiry,no_response_24h,no_response_48h,no_response_72h,follow_up,appointment_reminder,property_match,price_change',
            'delay_minutes' => 'nullable|integer|min:0',
            'template_id' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ];
    }
}
