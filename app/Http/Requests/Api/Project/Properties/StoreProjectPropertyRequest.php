<?php

namespace App\Http\Requests\Api\Project\Properties;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Api\Project\Properties\Concerns\NormalizesProjectPropertyLocation;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;

class StoreProjectPropertyRequest extends BaseApiFormRequest
{
    use NormalizesProjectPropertyLocation;
    use ValidatesPropertyListingStatus;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->propertyListingStatusRules(), [
            'title' => 'required|max:255',
            'address' => 'nullable|string|max:255',
            'description' => 'required',
            'featured_image' => 'required|string',
            'gallery' => 'nullable|array',
            'gallery.*' => 'string',
            'price' => 'nullable|numeric',
            'pricePerMeter' => 'nullable|numeric',
            'purpose' => 'nullable|in:sale,rent',
            'area' => 'nullable|numeric',
            'status' => 'nullable',
            'latitude' => ['nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'category_id' => 'nullable|integer',
            'advertising_license' => 'nullable|string',
            'featured' => 'nullable|boolean',
            'property_type' => 'prohibited',
            'project_id' => 'prohibited',
        ], $this->locationRules(true));
    }
}
