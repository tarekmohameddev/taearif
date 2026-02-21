<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class ListRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'action' => 'nullable|in:list,stats',
            'includeStats' => 'nullable|boolean',
            'filters' => 'nullable|array',
            'filters.search' => 'nullable|string|max:255',
            'filters.stage' => 'nullable|array',
            'filters.priority' => 'nullable|array',
            'filters.type' => 'nullable|array',
            'filters.source' => 'nullable|array',
            'filters.assignedEmployeeId' => 'nullable|integer',
            'filters.city' => 'nullable|integer',
            'filters.createdFrom' => 'nullable|date',
            'filters.createdTo' => 'nullable|date',
            'filters.sort_by' => 'nullable|in:created_at,updated_at,name',
            'filters.sort_dir' => 'nullable|in:asc,desc',
            'pagination' => 'nullable|array',
            'pagination.page' => 'nullable|integer|min:1',
            'pagination.limit' => 'nullable|integer|min:1|max:100',
        ];
    }
}
