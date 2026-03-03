<?php

namespace App\Http\Requests\Api\V1\Email;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreTemplateRequest extends BaseApiFormRequest
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
                'max:255',
                Rule::unique('email_campaign_templates', 'name')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ];
    }
}
