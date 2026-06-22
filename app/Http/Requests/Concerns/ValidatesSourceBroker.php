<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;

trait ValidatesSourceBroker
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function sourceBrokerRules(): array
    {
        $user = $this->user();
        $tenantOwnerId = $user && method_exists($user, 'tenantOwnerId')
            ? (int) $user->tenantOwnerId()
            : (int) ($user?->id ?? 0);

        $allowedBrokerIds = User::query()
            ->where(function ($query) use ($tenantOwnerId) {
                $query->where('id', $tenantOwnerId)
                    ->orWhere(function ($query) use ($tenantOwnerId) {
                        $query->where('tenant_id', $tenantOwnerId)
                            ->where('account_type', 'employee');
                    });
            })
            ->pluck('id')
            ->all();

        return [
            'source_broker_type' => ['nullable', 'in:internal,external'],
            'source_broker_id' => ['nullable', 'integer', Rule::in($allowedBrokerIds)],
            'source_broker_name' => ['nullable', 'string', 'max:191'],
            'source_broker_phone' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function validateSourceBroker($validator): void
    {
        $validator->after(function ($validator) {
            $type = $this->input('source_broker_type');

            if ($type === 'internal' && ! $this->filled('source_broker_id')) {
                $validator->errors()->add(
                    'source_broker_id',
                    'source_broker_id is required when source_broker_type is internal.'
                );
            }

            if ($type === 'external') {
                if (! $this->filled('source_broker_name')) {
                    $validator->errors()->add(
                        'source_broker_name',
                        'source_broker_name is required when source_broker_type is external.'
                    );
                }

                if (! $this->filled('source_broker_phone')) {
                    $validator->errors()->add(
                        'source_broker_phone',
                        'source_broker_phone is required when source_broker_type is external.'
                    );
                }
            }
        });
    }
}
