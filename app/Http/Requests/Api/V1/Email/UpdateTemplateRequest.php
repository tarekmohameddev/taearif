<?php

namespace App\Http\Requests\Api\V1\Email;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateTemplateRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = (int) (optional(auth()->user())->tenantOwnerId() ?? auth()->id() ?? 0);
        $id = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('email_campaign_templates', 'name')
                    ->ignore($id)
                    ->where(function ($q) use ($userId) {
                        return $q->where('user_id', $userId);
                    }),
            ],
            'subject' => 'sometimes|string|max:500',
            'body_html' => 'sometimes|string',
            'body_text' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'meta' => 'nullable|array',
        ];
    }
}
