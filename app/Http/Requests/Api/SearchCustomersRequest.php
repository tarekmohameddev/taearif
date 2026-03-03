<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\BaseApiFormRequest;

class SearchCustomersRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'city_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
            'priority_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',
            'responsible_employee_id' => 'nullable|integer',
            'employee_whatsapp_number' => 'nullable|string|max:20',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'sort_by' => 'nullable|in:name,email,phone_number,created_at,priority_id,type_id,stage_id,procedure_id,city_id',
            'sort_dir' => 'nullable|in:asc,desc',
        ];
    }
}
