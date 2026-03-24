<?php

namespace App\Http\Requests\Api\V2\CustomersHub;

use Illuminate\Foundation\Http\FormRequest;

class CompleteDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_type'  => ['sometimes', 'nullable', 'string', 'max:100'],
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
        ];
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
