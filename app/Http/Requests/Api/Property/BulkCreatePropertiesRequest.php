<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use Illuminate\Validation\Rule;

class BulkCreatePropertiesRequest extends BaseApiFormRequest
{
    use ValidatesTenantProjectId;

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();
    }
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'units' => 'required|array|min:1',
            'units.*.title' => 'required|string|max:255',
            'building_id' => 'nullable|integer',
            'publish_status' => ['nullable', Rule::in(['draft', 'published'])],
        ], $this->tenantProjectIdRules());
    }
}
