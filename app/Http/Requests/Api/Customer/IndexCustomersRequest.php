<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;

class IndexCustomersRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $toIntArray = function ($v): array {
            if (is_null($v) || $v === '') return [];
            if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
            if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
            if (is_array($v)) return array_values(array_filter(array_map('intval', $v)));
            return [];
        };
        $this->merge([
            'interested_category_ids' => $toIntArray($this->input('interested_category_ids')),
            'interested_property_ids' => $toIntArray($this->input('interested_property_ids')),
        ]);
    }

    public function rules()
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'q' => 'nullable|string|max:255',
            'city_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'type_id' => 'nullable|integer',
            'priority_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
            'stage_id' => 'nullable|integer',
            'phone_number' => 'nullable|string|max:20',
            'responsible_employee_id' => 'nullable|integer',
            'employee_whatsapp_number' => 'nullable|string|max:20',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'sort_by' => 'nullable|in:name,created_at,updated_at,priority_id',
            'sort_dir' => 'nullable|in:asc,desc',
            'interested_category_ids' => 'nullable|array',
            'interested_category_ids.*' => 'integer',
            'interested_property_ids' => 'nullable|array',
            'interested_property_ids.*' => 'integer',
            'include_interested' => 'nullable|boolean',
        ];
    }
}
