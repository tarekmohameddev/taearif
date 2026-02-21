<?php

namespace App\Http\Requests\Api\V1\Em;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $user = auth()->user();
        $tenantId = $user ? (method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id) : 0;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:2000'],
            'stage_id' => ['nullable', 'integer', Rule::exists('users_api_customers_stages', 'id')->where(fn($q) => $q->where('user_id', $tenantId))],
            'procedure_id' => ['nullable', 'integer'],
            'type_id' => ['nullable', 'integer'],
            'priority_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
        ];
    }
}
