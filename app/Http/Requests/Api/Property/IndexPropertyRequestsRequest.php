<?php

namespace App\Http\Requests\Api\property;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class IndexPropertyRequestsRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $ownerId = auth()->user() && method_exists(auth()->user(), 'tenantOwnerId')
            ? (int) auth()->user()->tenantOwnerId()
            : (int) auth()->id();

        return [
            'q' => 'nullable|string|max:255',
            'property_type' => 'nullable|string|max:255',
            'category_id' => 'nullable',
            'category' => 'nullable',
            'city_id' => 'nullable|integer',
            'districts_id' => 'nullable|integer',
            'district_id' => 'nullable|integer',
            'region' => 'nullable|string|max:255',
            'budget_from' => 'nullable|numeric|min:0',
            'budget_to' => 'nullable|numeric|min:0',
            'area_from' => 'nullable|integer|min:0',
            'area_to' => 'nullable|integer|min:0',
            'purchase_method' => 'nullable|string|max:50',
            'seriousness' => 'nullable|string|max:80',
            'purchase_goal' => 'nullable|string|max:80',
            'wants_similar_offers' => 'nullable|boolean',
            'contact_on_whatsapp' => 'nullable|boolean',
            'is_read' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'status_id' => 'nullable|integer|exists:property_request_statuses,id',
            'responsible_employee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($q) => $q->where('tenant_id', $ownerId)->where('account_type', 'employee')),
            ],
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date',
            'per_page' => 'nullable|integer|min:1|max:100',
            'with_statistics' => 'nullable|boolean',
            'cursor' => 'nullable|string',
        ];
    }
}
