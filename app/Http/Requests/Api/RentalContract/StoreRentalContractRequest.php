<?php

namespace App\Http\Requests\Api\RentalContract;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreRentalContractRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
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
        ];
    }
}
