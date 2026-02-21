<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateWaNumberRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,pending',
            'quota_limit' => 'nullable|integer|min:0',
            'phone_number_id' => 'nullable|string|max:191',
            'provider_account_id' => 'nullable|string|max:191',
            'meta' => 'nullable|array',
        ];
    }
}
