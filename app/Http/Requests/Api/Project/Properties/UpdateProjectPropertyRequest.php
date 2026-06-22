<?php

namespace App\Http\Requests\Api\Project\Properties;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Api\Project\Properties\Concerns\NormalizesProjectPropertyLocation;
use App\Http\Requests\Concerns\ValidatesPropertyListingStatus;
use App\Http\Requests\Concerns\ValidatesTenantCustomerId;

class UpdateProjectPropertyRequest extends BaseApiFormRequest
{
    use NormalizesProjectPropertyLocation;
    use ValidatesPropertyListingStatus;
    use ValidatesTenantCustomerId;

    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $this->validateReservedRequiresCustomer($validator);
    }

    public function rules(): array
    {
        return array_merge($this->propertyListingStatusRules(), $this->tenantCustomerIdRules(sometimes: true), [
            'title' => 'sometimes|required|max:255',
            'address' => 'sometimes|required',
            'description' => 'sometimes|required',
            'featured_image' => 'sometimes|required|string',
            'gallery' => 'sometimes|nullable|array',
            'gallery.*' => 'string',
            'price' => 'sometimes|nullable|numeric',
            'pricePerMeter' => 'sometimes|nullable|numeric',
            'purpose' => 'sometimes|nullable|in:sale,rent',
            'area' => 'sometimes|nullable|numeric',
            'status' => 'sometimes|nullable',
            'latitude' => ['sometimes', 'nullable', 'numeric', 'regex:/^[-]?((([0-8]?[0-9])\.(\d+))|(90(\.0+)?))$/'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'regex:/^[-]?((([1]?[0-7]?[0-9])\.(\d+))|([0-9]?[0-9])\.(\d+)|(180(\.0+)?))$/'],
            'category_id' => 'sometimes|nullable|integer',
            'advertising_license' => 'sometimes|nullable|string',
            'featured' => 'sometimes|nullable|boolean',
            'property_type' => 'prohibited',
            'project_id' => 'prohibited',
        ], $this->locationRules(false));
    }
}
