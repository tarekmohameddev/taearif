<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProcedureRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = auth()->user();
        $id = request()->route('id');

        return [
            'procedure_name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users_api_customers_procedures', 'procedure_name')
                    ->where(fn($q) => $q->where('user_id', $user ? $user->id : null))
                    ->ignore($id),
            ],
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'order' => 'sometimes|integer|min:1',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
