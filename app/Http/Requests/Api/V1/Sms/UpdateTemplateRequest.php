<?php

namespace App\Http\Requests\Api\V1\Sms;

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
                Rule::unique('sms_templates', 'name')
                    ->ignore($id)
                    ->where(function ($q) use ($userId) {
                        return $q->where('user_id', $userId);
                    }),
            ],
            'content' => 'sometimes|string',
            'category' => 'sometimes|in:promotional,transactional,reminder,notification,follow_up',
            'variables' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ];
    }
}
