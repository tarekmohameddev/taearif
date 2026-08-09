<?php

namespace App\Http\Requests\Api\RentalContract;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreRentalContractRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

        return [
            'rental_id' => [
                'required',
                Rule::exists('rm_rentals', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
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
