<?php

namespace App\Http\Requests\Api\RentalContract;

use App\Http\Requests\Api\BaseApiFormRequest;

class ChangeRentalContractStatusRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'status' => 'required|in:pending,active,expired,terminated',
            'reason' => 'nullable|string|max:255',
            'effective_date' => 'nullable|date',
        ];
    }
}
