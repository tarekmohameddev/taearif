<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreConversationRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'external_party_identifier' => 'required|string|max:191',
            'wa_number_id' => 'nullable|integer',
        ];
    }
}
