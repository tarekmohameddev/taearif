<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class IndexCrmRequestsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'q' => 'nullable|string|max:255',
            'customer_id' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'city_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
            'priority_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',
            'responsible_employee_id' => 'nullable|integer',
            'employee_whatsapp_number' => 'nullable|string|max:20',
            'interested_category_ids' => 'nullable',
            'interested_property_ids' => 'nullable',
            'has_property' => 'nullable|in:0,1',
            'reminder_type_id' => 'nullable|integer',
            'sort_by' => 'nullable|in:position,created_at,id',
            'sort_dir' => 'nullable|in:asc,desc',
        ];
    }
}
