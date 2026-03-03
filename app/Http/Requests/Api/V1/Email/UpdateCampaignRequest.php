<?php

namespace App\Http\Requests\Api\V1\Email;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $userId = (int) (optional(auth()->user())->tenantOwnerId() ?? auth()->id() ?? 0);

        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'sometimes|string|max:255',
            'body_html' => 'sometimes|string',
            'body_text' => 'nullable|string',
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('email_campaign_templates', 'id')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'status' => 'sometimes|in:draft,scheduled',
            'scheduled_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ];
    }
}
