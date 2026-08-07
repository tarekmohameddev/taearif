<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use App\Rules\PropertyTypeRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteDataRequest extends FormRequest
{
    use ValidatesTenantProjectId;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();

        if ($this->has('property_type')) {
            $normalized = \App\Rules\PropertyTypeRule::normalize(is_string($this->input('property_type')) ? $this->input('property_type') : null);
            if ($normalized !== null) {
                $this->merge(['property_type' => $normalized]);
            }
        }
    }

    public function rules(): array
    {
        return array_merge([
            'property_type'  => ['sometimes', ...PropertyTypeRule::nullableRule()],
            'purpose'        => ['sometimes', 'nullable', 'string', 'in:rent,sale,buy,invest'],
            'city'           => ['sometimes', 'nullable', 'string', 'max:255'],
            'district'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'region'         => ['sometimes', 'nullable', 'string', 'max:255'],
            'city_id'        => ['sometimes', 'nullable', 'integer', 'min:1'],
            'category_id'    => ['sometimes', 'nullable', 'integer', 'min:1'],
            'budget_from'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'budget_to'      => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'currency'       => ['sometimes', 'nullable', 'string', 'max:10'],
            'bedrooms'       => ['sometimes', 'nullable', 'integer', 'min:0'],
            'bathrooms'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'area_from'      => ['sometimes', 'nullable', 'integer', 'min:0'],
            'area_to'        => ['sometimes', 'nullable', 'integer', 'min:0'],
            'latitude'       => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ], $this->tenantProjectIdRules(sometimes: true));
    }

    /**
     * Return only the fields explicitly provided (non-null in input).
     * This prevents overwriting existing values with null.
     */
    public function onlyProvided(): array
    {
        $validated = $this->validated();
        return array_filter($validated, fn ($v) => $v !== null);
    }
}
