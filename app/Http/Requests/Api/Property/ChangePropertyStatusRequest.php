<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Http\Requests\Concerns\ValidatesTenantCustomerId;
use App\Rules\ValidListingPurposeUnitStatusCombination;
use Illuminate\Validation\Rule;

class ChangePropertyStatusRequest extends BaseApiFormRequest
{
    use ValidatesTenantCustomerId;
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'unit_status' => ['required', Rule::in(['available', 'reserved', 'sold', 'rented'])],
            'reason' => 'nullable|string|max:500',
            'listing_purpose' => ['nullable', Rule::in(['sale', 'rent'])],
        ], $this->tenantCustomerIdRules());
    }

    public function withValidator($validator): void
    {
        $this->validateReservedRequiresCustomer($validator);

        $validator->after(function ($validator) {
            $property = $this->route('property') ?? $this->route('id');
            if (! $property) {
                return;
            }

            $model = \App\Models\User\RealestateManagement\Property::find(
                is_object($property) ? $property->id : $property
            );
            if (! $model) {
                return;
            }

            $listingPurpose = $this->input('listing_purpose', $model->listing_purpose ?? $model->purpose);
            $rule = new ValidListingPurposeUnitStatusCombination([
                'listing_purpose' => $listingPurpose,
                'unit_status' => $this->input('unit_status'),
            ]);
            if (! $rule->passes('unit_status', $this->input('unit_status'))) {
                $validator->errors()->add('unit_status', $rule->message());
            }
        });
    }
}
