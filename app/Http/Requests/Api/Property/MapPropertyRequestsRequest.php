<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\PropertyTypeRule;
use Illuminate\Validation\Rule;

class MapPropertyRequestsRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('property_type')) {
            $normalized = PropertyTypeRule::normalize(
                is_string($this->input('property_type')) ? $this->input('property_type') : null
            );
            if ($normalized !== null) {
                $this->merge(['property_type' => $normalized]);
            }
        }
    }

    public function rules(): array
    {
        $ownerId = auth()->user() && method_exists(auth()->user(), 'tenantOwnerId')
            ? (int) auth()->user()->tenantOwnerId()
            : (int) auth()->id();

        return [
            'city_id' => 'nullable|integer',
            'districts_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'property_type' => PropertyTypeRule::nullableRule(),
            'status_id' => 'nullable|integer|exists:property_request_statuses,id',
            'responsible_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $ownerId)->where('account_type', 'employee')),
            ],
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'include_archived' => 'nullable|boolean',
            'include_inactive' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:5000',
        ];
    }
}
