<?php

namespace App\Http\Requests\Api\Crm;

use App\Http\Requests\Api\BaseApiFormRequest;

class UpdateCrmRequestRequest extends BaseApiFormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'stage_id' => ['sometimes', 'nullable', 'integer', 'exists:users_api_customers_stages,id'],
            'customer_name' => ['sometimes', 'required', 'string', 'max:255'],
            'customer_phone' => ['sometimes', 'required', 'string', 'max:32'],
            'property_id' => ['nullable', 'integer'],
            'property_specifications' => ['nullable', 'array'],
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
            'position' => ['nullable', 'integer', 'min:0'],
            'payment_method' => ['nullable'],
            'price' => ['nullable', 'numeric'],
            'pricePerMeter' => ['nullable', 'numeric'],
            'purpose' => ['nullable'],
            'type' => ['nullable'],
            'beds' => ['nullable', 'integer'],
            'bath' => ['nullable', 'integer'],
            'area' => ['nullable', 'numeric'],
            'status' => ['nullable', 'integer'],
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'project_id' => ['nullable', 'integer'],
            'region_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'features' => ['nullable', 'array'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'water_meter_number' => ['nullable', 'string'],
            'electricity_meter_number' => ['nullable', 'string'],
            'deed_number' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string'],
            'virtual_tour' => ['nullable', 'string'],
            'size' => ['nullable', 'numeric'],
            'address' => ['nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'city_id' => ['nullable', 'integer'],
            'state_id' => ['nullable', 'integer'],
            'facade_id' => ['nullable', 'numeric'],
            'length' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'street_width_north' => ['nullable', 'numeric'],
            'street_width_south' => ['nullable', 'numeric'],
            'street_width_east' => ['nullable', 'numeric'],
            'street_width_west' => ['nullable', 'numeric'],
            'building_age' => ['nullable', 'integer'],
            'rooms' => ['nullable', 'integer'],
            'bathrooms' => ['nullable', 'integer'],
            'floors' => ['nullable', 'integer'],
            'floor_number' => ['nullable', 'integer'],
            'driver_room' => ['nullable', 'integer'],
            'maid_room' => ['nullable', 'integer'],
            'dining_room' => ['nullable', 'integer'],
            'living_room' => ['nullable', 'integer'],
            'majlis' => ['nullable', 'integer'],
            'storage_room' => ['nullable', 'integer'],
            'basement' => ['nullable', 'integer'],
            'swimming_pool' => ['nullable', 'integer'],
            'kitchen' => ['nullable', 'integer'],
            'balcony' => ['nullable', 'integer'],
            'garden' => ['nullable', 'integer'],
            'annex' => ['nullable', 'integer'],
            'elevator' => ['nullable', 'integer'],
            'private_parking' => ['nullable', 'integer'],
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
