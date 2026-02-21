<?php

namespace App\Http\Requests\Api\Contract;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreApiContractRequest extends BaseApiFormRequest
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
                'rental_id' => 'required|exists:rm_rentals,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:pending,active',
                'file_path' => 'nullable|string|max:255',
                'property_id' => 'nullable|integer|min:1',
                'project_id' => 'nullable|integer|min:1',
                'property_name' => 'nullable|string|max:150',
                'project_name' => 'nullable|string|max:150',
                'grace_period_months' => 'nullable|integer|min:0|max:2',
            ]);
        }

        return array_merge($rules, [
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'contract_value' => 'required|numeric|min:0',
            'contract_type' => 'required|string|in:Standard,Contracts under Seal,Lease Agreement,Other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'contract_status' => 'required|in:draft,signed,expired',
        ]);
    }
}
