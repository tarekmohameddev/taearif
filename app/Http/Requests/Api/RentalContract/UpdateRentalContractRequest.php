<?php

namespace App\Http\Requests\Api\RentalContract;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateRentalContractRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'status' => 'sometimes|in:pending,active,expired,terminated',
            'file_path' => 'sometimes|string|max:255',
            'property_id' => 'sometimes|nullable|integer|min:1',
            'project_id' => 'sometimes|nullable|integer|min:1',
            'property_name' => 'sometimes|nullable|string|max:150',
            'project_name' => 'sometimes|nullable|string|max:150',
            'grace_period_months' => 'sometimes|nullable|integer|min:0|max:2',
        ];
    }
}
