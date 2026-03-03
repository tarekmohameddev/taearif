<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerTypeRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = auth()->user();

        return [
            'name' => 'required|string|max:255',
            'value' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users_api_customers_types', 'value')
                    ->where(fn($q) => $q->where('user_id', $user ? $user->id : null)),
            ],
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'order' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
