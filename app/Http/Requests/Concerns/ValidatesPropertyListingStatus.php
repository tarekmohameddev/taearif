<?php

namespace App\Http\Requests\Concerns;

use App\Rules\ValidListingPurposeUnitStatusCombination;
use Illuminate\Validation\Rule;

trait ValidatesPropertyListingStatus
{
    /**
     * @return array<string, mixed>
     */
    protected function propertyListingStatusRules(): array
    {
        $payload = $this->all();

        return [
            'listing_purpose' => ['nullable', Rule::in(['sale', 'rent'])],
            'unit_status' => [
                'nullable',
                Rule::in(['available', 'reserved', 'sold', 'rented']),
                function ($attribute, $value, $fail) use ($payload) {
                    $rule = new ValidListingPurposeUnitStatusCombination($payload);
                    if (! $rule->passes($attribute, $value)) {
                        $fail($rule->message());
                    }
                },
            ],
            'publish_status' => ['nullable', Rule::in(['draft', 'published'])],
        ];
    }
}
