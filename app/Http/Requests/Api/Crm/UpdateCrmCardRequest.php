<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCrmCardRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tenantId = optional(auth()->user())->tenantOwnerId() ?? 0;

        return [
            'card_request_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('crm_requests', 'id')->where(fn($q) => $q->where('user_id', $tenantId)),
            ],
            'card_content' => ['nullable', 'string'],
            'card_procedure' => ['sometimes', 'required', Rule::in(['reminder', 'note', 'interaction', 'appointment'])],
            'card_project' => ['nullable', 'integer'],
            'card_property' => ['nullable', 'integer'],
            'card_date' => ['nullable', 'date'],
        ];
    }
}
