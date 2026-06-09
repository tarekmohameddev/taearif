<?php

namespace App\Http\Requests\Api\Project\Properties;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;
use Illuminate\Validation\Rule;

class ListProjectPropertiesRequest extends BaseApiFormRequest
{
    use ValidatesPropertyListingStatus;

    protected function prepareForValidation(): void
    {
        if ($this->has('district_id') && ! $this->filled('state_id')) {
            $this->merge(['state_id' => $this->input('district_id')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->propertyListingStatusRules(), [
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'category_id' => 'nullable|integer',
            'property_type' => 'nullable|string|max:100',
            'price_from' => 'nullable|numeric|min:0',
            'price_to' => 'nullable|numeric|min:0',
            'floor_number' => 'nullable|integer',
            'city_id' => 'nullable|integer',
            'state_id' => 'nullable|integer',
            'district_id' => 'nullable|integer|exists:user_districts,id',
            'payment_method' => ['nullable', Rule::in(['monthly', 'quarterly', 'semi_annual', 'annual'])],
            'search' => 'nullable|string|max:255',
        ]);
    }
}
