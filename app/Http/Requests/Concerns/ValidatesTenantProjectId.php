<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesTenantProjectId
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function tenantProjectIdRules(bool $sometimes = false): array
    {
        $user = $this->user();
        $tenantOwnerId = $user && method_exists($user, 'tenantOwnerId')
            ? (int) $user->tenantOwnerId()
            : (int) ($user?->id ?? 0);

        $rules = [
            'nullable',
            'integer',
            Rule::exists('user_projects', 'id')->where('user_id', $tenantOwnerId),
        ];

        if ($sometimes) {
            array_unshift($rules, 'sometimes');
        }

        return ['project_id' => $rules];
    }

    protected function normalizeNullableProjectId(): void
    {
        if ($this->has('project_id') && $this->input('project_id') === '') {
            $this->merge(['project_id' => null]);
        }
    }
}
