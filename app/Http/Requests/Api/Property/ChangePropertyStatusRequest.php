<?php

namespace App\Http\Requests\Api\Property;

use App\Http\Requests\Api\BaseApiFormRequest;
use App\Rules\ValidListingPurposeUnitStatusCombination;
use Illuminate\Validation\Rule;

class ChangePropertyStatusRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'unit_status' => ['required', Rule::in(['available', 'reserved', 'sold', 'rented'])],
            'reason' => 'nullable|string|max:500',
            'customer_id' => 'nullable|integer|exists:api_customers,id',
            'listing_purpose' => ['nullable', Rule::in(['sale', 'rent'])],
        ];
    }

    public function withValidator($validator): void
    {
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

            if ($this->input('unit_status') === 'reserved' && ! $this->input('customer_id')) {
                $validator->errors()->add('customer_id', 'customer_id is required when unit_status is reserved.');
            }
        });
    }
}
