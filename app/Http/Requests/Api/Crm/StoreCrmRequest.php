<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class StoreCrmRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:32'],
            'stage_id' => ['nullable', 'integer', 'exists:users_api_customers_stages,id'],
            'property_id' => ['nullable', 'integer', 'required_without:property_specifications'],
            'property_specifications' => ['nullable', 'array', 'required_without:property_id'],
            'property_specifications.basic_information' => ['nullable', 'array'],
            'property_specifications.basic_information.address' => ['nullable', 'string'],
            'property_specifications.basic_information.building' => ['nullable'],
            'property_specifications.basic_information.price' => ['nullable', 'numeric'],
            'property_specifications.basic_information.payment_method' => ['nullable'],
            'property_specifications.basic_information.price_per_sqm' => ['nullable', 'numeric'],
            'property_specifications.basic_information.listing_type' => ['nullable'],
            'property_specifications.basic_information.property_category' => ['nullable'],
            'property_specifications.basic_information.project' => ['nullable'],
            'property_specifications.basic_information.city' => ['nullable'],
            'property_specifications.basic_information.district' => ['nullable'],
            'property_specifications.basic_information.area' => ['nullable'],
            'property_specifications.basic_information.property_type' => ['nullable'],
            'property_specifications.details' => ['nullable', 'array'],
            'property_specifications.details.features' => ['nullable', 'array'],
            'property_specifications.attributes' => ['nullable', 'array'],
            'property_specifications.attributes.area_sqft' => ['nullable', 'numeric'],
            'property_specifications.attributes.year_built' => ['nullable', 'integer'],
            'property_specifications.facilities' => ['nullable', 'array'],
            'property_specifications.facilities.bedrooms' => ['nullable', 'integer'],
            'property_specifications.facilities.bathrooms' => ['nullable', 'integer'],
            'property_specifications.facilities.rooms' => ['nullable', 'integer'],
            'property_specifications.facilities.floors' => ['nullable', 'integer'],
            'property_specifications.facilities.floor_number' => ['nullable', 'integer'],
            'property_specifications.facilities.drivers_room' => ['nullable', 'boolean'],
            'property_specifications.facilities.maids_room' => ['nullable', 'boolean'],
            'property_specifications.facilities.dining_room' => ['nullable', 'boolean'],
            'property_specifications.facilities.living_room' => ['nullable', 'boolean'],
            'property_specifications.facilities.majlis' => ['nullable', 'boolean'],
            'property_specifications.facilities.storage_room' => ['nullable', 'boolean'],
            'property_specifications.facilities.basement' => ['nullable', 'boolean'],
            'property_specifications.facilities.swimming_pool' => ['nullable', 'boolean'],
            'property_specifications.facilities.kitchen' => ['nullable', 'boolean'],
            'property_specifications.facilities.balcony' => ['nullable', 'boolean'],
            'property_specifications.facilities.garden' => ['nullable', 'boolean'],
            'property_specifications.facilities.annex' => ['nullable', 'boolean'],
            'property_specifications.facilities.elevator' => ['nullable', 'boolean'],
            'property_specifications.facilities.parking_space' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            if (request()->filled('property_id') && request()->filled('property_specifications')) {
                $v->errors()->add('property_specifications', 'property_id and property_specifications cannot be used together.');
            }
        });
    }
}
