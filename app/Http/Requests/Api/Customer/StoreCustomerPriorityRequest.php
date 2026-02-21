<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerPriorityRequest extends BaseApiFormRequest
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
                'integer',
                Rule::unique('users_api_customers_priorities', 'value')->where(
                    fn($q) => $q->where('user_id', $user ? $user->id : null)
                ),
            ],
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'order' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
