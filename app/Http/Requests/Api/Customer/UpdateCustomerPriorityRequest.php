<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerPriorityRequest extends BaseApiFormRequest
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
            'name' => 'sometimes|string|max:255',
            'value' => [
                'sometimes',
                'integer',
                'in:1,2,3',
                Rule::unique('users_api_customers_priorities', 'value')
                    ->where(fn($q) => $q->where('user_id', $user ? $user->id : null))
                    ->ignore($id),
            ],
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
            'order' => 'sometimes|integer|min:1',
            'is_active' => 'boolean',
        ];
    }
}
