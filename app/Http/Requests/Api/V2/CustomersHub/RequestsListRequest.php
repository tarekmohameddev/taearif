<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Api\BaseApiFormRequest;

class RequestsListRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $r = request();
        $this->merge([
            'tab' => $r->input('activeTab') ?? $r->input('tab'),
            'types' => $r->input('selectedTypes') ?? $r->input('types'),
            'sources' => $r->input('selectedSources') ?? $r->input('sources'),
            'priorities' => $r->input('selectedPriorities') ?? $r->input('priorities'),
            'assignees' => $r->input('selectedAssignees') ?? $r->input('assignees'),
            'due_date_bucket' => $r->input('dueDateFilter') ?? $r->input('due_date_bucket'),
            'property_categories' => $r->input('selectedPropertyTypes') ?? $r->input('property_categories'),
            'cities' => $r->input('selectedCities') ?? $r->input('cities'),
            'states' => $r->input('selectedStates') ?? $r->input('states'),
            'budget_min' => $r->input('budgetMin') ?? $r->input('budget_min'),
            'budget_max' => $r->input('budgetMax') ?? $r->input('budget_max'),
            'objectTypes' => $r->input('selectedObjectTypes') ?? $r->input('objectTypes'),
            'stages' => $r->input('selectedStages') ?? $r->input('stages'),
        ]);
    }

    public function rules()
    {
        return [
            'tab' => 'nullable|in:inbox,followups,all,completed',
            'types' => 'nullable|array',
            'types.*' => 'string|in:new_inquiry,callback_request,whatsapp_incoming,property_match,follow_up,site_visit',
            'statuses' => 'nullable|array',
            'statuses.*' => 'string|in:pending,in_progress,completed,dismissed,snoozed',
            'sources' => 'nullable|array',
            'sources.*' => 'string|in:inquiry,manual,whatsapp,import,referral,property_request',
            'priorities' => 'nullable|array',
            'priorities.*' => 'string|in:low,medium,high,urgent',
            'assignees' => 'nullable|array',
            'assignees.*' => 'integer',
            'customer_id' => 'nullable|integer',
            'due_date_bucket' => 'nullable|in:overdue,today,week,no_date',
            'property_categories' => 'nullable|array',
            'property_categories.*' => 'string',
            'property_types' => 'nullable|array',
            'property_types.*' => 'string',
            'cities' => 'nullable|array',
            'cities.*' => 'string',
            'states' => 'nullable|array',
            'states.*' => 'string',
            'budget_min' => 'nullable|numeric|min:0',
            'budget_max' => 'nullable|numeric|min:0',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:createdAt,dueDate,priority,customerName',
            'sort_dir' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'objectTypes' => 'nullable|array',
            'objectTypes.*' => 'string|in:inquiry,property_request,reminder,request_appointment,request_reminder',
            'stages' => 'nullable|array',
            'stages.*' => 'integer',
        ];
    }
}
