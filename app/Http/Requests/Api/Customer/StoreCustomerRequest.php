<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $toIntArray = function ($v): array {
            if (is_null($v) || $v === '') return [];
            if (is_int($v) || (is_string($v) && is_numeric($v))) return [(int)$v];
            if (is_string($v)) return array_values(array_filter(array_map('intval', explode(',', $v))));
            if (is_array($v)) return array_values(array_filter(array_map('intval', $v)));
            return [];
        };
        $this->merge([
            'interested_category_ids' => $toIntArray($this->input('interested_category_ids')),
            'interested_property_ids' => $toIntArray($this->input('interested_property_ids')),
        ]);
    }

    public function rules()
    {
        $userId = auth()->id();

        return [
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('api_customers', 'email')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'phone_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('api_customers', 'phone_number')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'city_id' => 'nullable|exists:user_cities,id',
            'district_id' => 'nullable|exists:user_districts,id',
            'note' => 'nullable|string',
            'type_id' => [
                'required',
                Rule::exists('users_api_customers_types', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'responsible_employee_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($q) {
                    $uid = auth()->user() && method_exists(auth()->user(), 'tenantOwnerId')
                        ? auth()->user()->tenantOwnerId()
                        : auth()->id();
                    $q->where('tenant_id', $uid)->where('account_type', 'employee')->where('active', true);
                }),
            ],
            'stage_id' => [
                'nullable',
                Rule::exists('users_api_customers_stages', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'procedure_id' => [
                'nullable',
                Rule::exists('users_api_customers_procedures', 'id')->where(fn ($q) => $q->where('user_id', $userId)),
            ],
            'password' => 'nullable|string|min:6',
            'interested_category_ids' => 'nullable|array',
            'interested_category_ids.*' => 'integer|exists:api_user_categories,id',
            'interested_property_ids' => 'nullable|array',
            'interested_property_ids.*' => 'integer|exists:user_properties,id',
        ];
    }
}
