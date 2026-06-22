<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesTenantCustomerId
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function tenantCustomerIdRules(bool $sometimes = false): array
    {
        $user = $this->user();
        $tenantOwnerId = $user && method_exists($user, 'tenantOwnerId')
            ? (int) $user->tenantOwnerId()
            : (int) ($user?->id ?? 0);

        $rules = [
            'nullable',
            'integer',
            Rule::exists('api_customers', 'id')->where('user_id', $tenantOwnerId),
        ];

        if ($sometimes) {
            array_unshift($rules, 'sometimes');
        }

        return ['customer_id' => $rules];
    }

    protected function validateReservedRequiresCustomer($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('unit_status') === 'reserved' && ! $this->input('customer_id')) {
                $validator->errors()->add('customer_id', 'customer_id is required when unit_status is reserved.');
            }
        });
    }
}
