<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\PropertyTypeRule;

class PipelineIndexRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $filters = $this->input('filters');
        if (!is_array($filters)) {
            return;
        }

        if (array_key_exists('property_type', $filters) && is_array($filters['property_type'])) {
            $filters['property_type'] = array_values(array_filter(array_map(
                static fn ($v) => PropertyTypeRule::normalize(is_string($v) ? $v : null),
                $filters['property_type']
            )));
            $this->merge(['filters' => $filters]);
        }
    }

    public function rules()
    {
        return [
            'action' => 'nullable|in:board,analytics,get_board',
            'includeAnalytics' => 'nullable|boolean',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|array',
            'filters.status.*' => 'integer',
            'filters.status_id' => 'nullable|array',
            'filters.stage_id' => 'nullable|array',
            'filters.property_type' => 'nullable|array',
            'filters.property_type.*' => ['string', new PropertyTypeRule()],
            'filters.city_id' => 'nullable|integer',
            'filters.district_id' => 'nullable|integer',
            'filters.districts_id' => 'nullable|integer',
            'filters.budget_from' => 'nullable|numeric',
            'filters.budget_to' => 'nullable|numeric',
            'filters.assignedEmployeeId' => 'nullable|integer',
            'filters.search' => 'nullable|string|max:255',
        ];
    }
}
