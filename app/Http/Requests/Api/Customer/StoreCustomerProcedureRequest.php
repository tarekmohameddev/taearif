<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerProcedureRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = auth()->user();

        return [
            'procedure_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users_api_customers_procedures', 'procedure_name')->where(
                    fn($q) => $q->where('user_id', $user ? $user->id : null)
                ),
            ],
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'order' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
