<?php

namespace App\Http\Requests\Api\V1\Rms;

use App\Http\Requests\Api\BaseApiFormRequest;

class AllContractsRequest extends BaseApiFormRequest
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
            'building_id' => 'nullable|integer',
            'payment_status' => 'nullable|in:paid,pending,overdue,not_due',
            'rental_method' => 'nullable|in:monthly,quarterly,semi_annual,annual',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'contract_status' => 'nullable|in:active,expired,pending,terminated',
        ];
    }
}
