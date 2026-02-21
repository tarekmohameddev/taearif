<?php

namespace App\Http\Requests\Api\V1\Matching;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateCustomerRequestEntryRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'purpose' => ['sometimes', 'nullable'],
            'category_id' => ['sometimes', 'nullable', 'integer'],
            'property_type' => ['sometimes', 'nullable'],
            'budget_from' => ['sometimes', 'nullable', 'numeric'],
            'budget_to' => ['sometimes', 'nullable', 'numeric'],
            'area_from' => ['sometimes', 'nullable', 'numeric'],
            'area_to' => ['sometimes', 'nullable', 'numeric'],
            'city_id' => ['sometimes', 'nullable', 'integer'],
            'districts_id' => ['sometimes', 'nullable'],
            'region' => ['sometimes', 'nullable'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'inquiry_type' => ['sometimes', 'nullable'],
            'budget' => ['sometimes', 'nullable', 'numeric'],
            'currency' => ['sometimes', 'nullable', 'string'],
            'bedrooms' => ['sometimes', 'nullable', 'integer'],
            'bathrooms' => ['sometimes', 'nullable', 'integer'],
            'min_area_sqm' => ['sometimes', 'nullable', 'numeric'],
            'max_area_sqm' => ['sometimes', 'nullable', 'numeric'],
            'furnished' => ['sometimes', 'nullable', 'boolean'],
            'urgency' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string'],
            'region_name' => ['sometimes', 'nullable', 'string'],
            'region_code' => ['sometimes', 'nullable', 'string'],
            'city' => ['sometimes', 'nullable', 'string'],
            'district' => ['sometimes', 'nullable', 'string'],
            'latitude' => ['sometimes', 'nullable', 'numeric'],
            'longitude' => ['sometimes', 'nullable', 'numeric'],
            'message' => ['sometimes', 'nullable', 'string'],
            'lang' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
