<?php

namespace App\Http\Requests\Api\V1\WhatsApp;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateWaCampaignRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (int) (optional(auth()->user())->tenantOwnerId() ?? auth()->id() ?? 0);

        return [
            'wa_number_id' => [
                'sometimes',
                'integer',
                Rule::exists('wa_numbers', 'id')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'message' => 'nullable|string',
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('wa_templates', 'id')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'status' => 'sometimes|in:draft,scheduled',
            'scheduled_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $message = $this->input('message');
            $templateId = $this->input('template_id');
            $hasMessage = $message !== null && trim((string) $message) !== '';
            $hasTemplate = $templateId !== null && $templateId !== '';
            if ($hasMessage && $hasTemplate) {
                $validator->errors()->add('content', 'WA_CAMPAIGN_CONTENT_CONFLICT');
            }
        });
    }
}
