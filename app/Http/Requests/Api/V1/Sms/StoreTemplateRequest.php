<?php

namespace App\Http\Requests\Api\V1\Sms;

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
                Rule::unique('sms_templates', 'name')->where(function ($q) use ($userId) {
                    return $q->where('user_id', $userId);
                }),
            ],
            'content' => 'required|string',
            'category' => 'required|in:promotional,transactional,reminder,notification,follow_up',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ];
    }
}
