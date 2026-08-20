<?php

namespace App\Http\Requests\Api\Customer;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Models\Api\UserApiCustomerType;
use App\Services\PropertyRequestCustomerService;
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

        $merge = [
            'interested_category_ids' => $toIntArray($this->input('interested_category_ids')),
            'interested_property_ids' => $toIntArray($this->input('interested_property_ids')),
        ];

        $typeId = $this->input('type_id');
        if ($typeId === null || $typeId === '') {
            $merge['type_id'] = $this->defaultTypeId();
        }

        $this->merge($merge);
    }

    public function rules()
    {
        $userId = auth()->id();
        $ownerId = $this->tenantOwnerId();

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
                'nullable',
                Rule::exists('users_api_customers_types', 'id')->where(fn ($q) => $q->where('user_id', $ownerId)),
            ],
            'responsible_employee_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($q) use ($ownerId) {
                    $q->where('tenant_id', $ownerId)->where('account_type', 'employee')->where('active', true);
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

    /**
     * Resolve the tenant's default customer type when the client omits type_id.
     *
     * The cached defaults are shared with the auto-customer flows and are only
     * invalidated on settings changes, so they can hold a null type_id for up to
     * the cache TTL if they were warmed before the tenant's CRM types existed.
     * Fall back to a direct lookup in that case so an omitted type_id does not
     * silently store a customer with no type.
     */
    private function defaultTypeId(): ?int
    {
        $ownerId = $this->tenantOwnerId();

        $defaults = app(PropertyRequestCustomerService::class)
            ->getDefaultCustomerAttributes($ownerId);

        if (! empty($defaults['type_id'])) {
            return (int) $defaults['type_id'];
        }

        return UserApiCustomerType::where('user_id', $ownerId)
            ->where('is_active', true)
            ->orderBy('order')
            ->value('id');
    }

    private function tenantOwnerId(): int
    {
        $user = auth()->user();

        return $user && method_exists($user, 'tenantOwnerId')
            ? (int) $user->tenantOwnerId()
            : (int) (auth()->id() ?? 0);
    }
}
