<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;
use App\Http\Requests\Concerns\ValidatesSourceBroker;
use App\Http\Requests\Concerns\ValidatesTenantCustomerId;
use App\Http\Requests\Concerns\ValidatesTenantProjectId;
use App\Rules\PropertyTypeRule;

class StorePropertyRequest extends BaseApiFormRequest
{
    use ValidatesPropertyListingStatus;
    use ValidatesSourceBroker;
    use ValidatesTenantCustomerId;
    use ValidatesTenantProjectId;
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableProjectId();

        if ($this->has('property_type')) {
            $normalized = PropertyTypeRule::normalize(is_string($this->input('property_type')) ? $this->input('property_type') : null);
            if ($normalized !== null) {
                $this->merge(['property_type' => $normalized]);
            }
        }
    }

    public function withValidator($validator): void
    {
        $this->validateReservedRequiresCustomer($validator);
        $this->validateSourceBroker($validator);
    }

    public function rules()
    {
        return array_merge(
            $this->propertyListingStatusRules(),
            $this->tenantProjectIdRules(),
            $this->tenantCustomerIdRules(sometimes: true),
            $this->sourceBrokerRules(),
            [
            'payment_method' => 'nullable',
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'floor_planning_image' => ['nullable'],
            'video_image' => 'nullable|string',
            'video_url' => 'nullable|string',
            'virtual_tour' => 'nullable|string',
            'price' => 'nullable|numeric',
            'pricePerMeter' => 'nullable|numeric',
            'beds' => 'nullable',
            'bath' => 'nullable',
            'purpose' => 'nullable',
            'area' => 'nullable',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'city_id' => 'nullable',
            'state_id' => 'nullable',
            'featured' => 'nullable|boolean',
            'amenities' => 'nullable|array',
            'property_type' => PropertyTypeRule::requiredRule(),
            'faqs' => 'nullable|array',
            'external_links'            => 'nullable|array',
            'external_links.*.platform' => 'required_with:external_links|string|max:60',
            'external_links.*.url'      => 'required_with:external_links|url|max:2048',
            'external_links.*.label'    => 'nullable|string|max:120',
            'external_links.*.active'   => 'nullable|boolean',
            'category_id' => 'nullable|integer',
            'facade_id' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'street_width_north' => 'nullable|numeric',
            'street_width_south' => 'nullable|numeric',
            'street_width_east' => 'nullable|numeric',
            'street_width_west' => 'nullable|numeric',
            'building_age' => 'nullable|integer',
            'rooms' => 'nullable|integer',
            'bathrooms' => 'nullable|integer',
            'floors' => 'nullable|integer',
            'floor_number' => 'nullable|integer',
            'driver_room' => 'nullable|integer',
            'maid_room' => 'nullable|integer',
            'dining_room' => 'nullable|integer',
            'living_room' => 'nullable|integer',
            'majlis' => 'nullable|integer',
            'storage_room' => 'nullable|integer',
            'basement' => 'nullable|integer',
            'swimming_pool' => 'nullable|integer',
            'kitchen' => 'nullable|integer',
            'balcony' => 'nullable|integer',
            'garden' => 'nullable|integer',
            'annex' => 'nullable|integer',
            'elevator' => 'nullable|integer',
            'private_parking' => 'nullable|integer',
            'size' => 'nullable|integer',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'water_meter_number' => 'nullable|string',
            'electricity_meter_number' => 'nullable|string',
            'deed_number' => 'nullable|string',
            'advertising_license' => 'nullable|string',
            'owner_number' => 'nullable|string',
            'video_file' => 'nullable|file',
            ]
        );
    }
}
