<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class DailyFollowUpRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'status' => 'nullable|string|in:overdue,due_today,upcoming',
        ];
    }
}
