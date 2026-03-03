<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreWaTemplateRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = (int) (optional(auth()->user())->tenantOwnerId() ?? auth()->id() ?? 0);

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('wa_templates', 'name')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'content' => 'required|string',
            'category' => 'nullable|string|max:50',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'language' => 'nullable|string|max:10',
        ];
    }
}
