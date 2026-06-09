<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;
use App\Http\Requests\Concerns\ValidatesTenantCustomerId;
use App\Rules\PropertyTypeRule;

class UpdatePropertyRequest extends BaseApiFormRequest
{
    use ValidatesPropertyListingStatus;
    use ValidatesTenantCustomerId;
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
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
    }

    public function rules()
    {
        return array_merge($this->propertyListingStatusRules(), $this->tenantCustomerIdRules(sometimes: true), [
            'payment_method' => 'nullable',
            'title' => 'required|max:255',
            'address' => 'required',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'floor_planning_image' => 'nullable',
            'video_image' => 'nullable|string',
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
            'amenities' => 'nullable|array',
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
            'size' => 'nullable|numeric',
            'source_broker_type' => 'nullable|in:internal,external',
            'source_broker_id' => 'nullable|integer|exists:users,id',
            'source_broker_name' => 'nullable|string|max:191',
            'source_broker_phone' => 'nullable|string|max:32',
            'property_type' => PropertyTypeRule::requiredRule(),
            'faqs' => 'nullable|array',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'project_id' => 'prohibited',
            'water_meter_number' => 'nullable|string',
            'electricity_meter_number' => 'nullable|string',
            'deed_number' => 'nullable|string',
            'advertising_license' => 'nullable|string',
            'owner_number' => 'nullable|string',
            'video_url' => 'nullable|string',
            'virtual_tour' => 'nullable|string',
            'video_file' => 'nullable|file',
            'show_reservations' => 'nullable|boolean',
        ]);
    }
}
