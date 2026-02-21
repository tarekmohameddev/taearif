<?php

namespace App\Http\Requests\Api\V1\Em;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends BaseApiFormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'note' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'stage_id' => ['sometimes', 'nullable', 'integer', Rule::exists('users_api_customers_stages', 'id')->where(fn($q) => $q->where('user_id', $tenantId))],
            'procedure_id' => ['sometimes', 'nullable', 'integer'],
            'type_id' => ['sometimes', 'nullable', 'integer'],
            'priority_id' => ['sometimes', 'nullable', 'integer'],
            'city_id' => ['sometimes', 'nullable', 'integer'],
            'district_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}
