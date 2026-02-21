<?php

namespace App\Http\Requests\Api\Contract;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateApiContractRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $rules = [
            'type' => 'nullable|in:regular,rms',
        ];

        $type = request()->input('type', 'regular');
        if ($type === 'rms') {
            return array_merge($rules, [
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after:start_date',
                'status' => 'sometimes|in:pending,active,expired,terminated',
                'file_path' => 'sometimes|string|max:255',
                'property_id' => 'sometimes|nullable|integer|min:1',
                'project_id' => 'sometimes|nullable|integer|min:1',
                'property_name' => 'sometimes|nullable|string|max:150',
                'project_name' => 'sometimes|nullable|string|max:150',
                'grace_period_months' => 'sometimes|nullable|integer|min:0|max:2',
            ]);
        }

        return array_merge($rules, [
            'customer_id' => 'sometimes|exists:customers,id',
            'subject' => 'sometimes|string|max:255',
            'contract_value' => 'sometimes|numeric|min:0',
            'contract_type' => 'sometimes|string|in:Standard,Contracts under Seal,Lease Agreement,Other',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'description' => 'sometimes|string',
            'contract_status' => 'sometimes|in:draft,signed,expired',
        ]);
    }
}
