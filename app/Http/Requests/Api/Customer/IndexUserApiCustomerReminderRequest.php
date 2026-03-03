<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class IndexUserApiCustomerReminderRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'filter_id' => 'nullable|integer',
            'filter_title' => 'nullable|string|max:255',
            'filter_datetime_from' => 'nullable|date',
            'filter_datetime_to' => 'nullable|date|after_or_equal:filter_datetime_from',
            'sort_by' => 'nullable|string|in:id,title,datetime,priority,created_at',
            'sort_dir' => 'nullable|string|in:asc,desc',
        ];
    }
}
