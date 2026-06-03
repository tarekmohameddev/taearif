<?php

namespace App\Rules;

use App\Services\Property\PropertyStatusSyncService;
use Illuminate\Contracts\Validation\Rule;

class ValidListingPurposeUnitStatusCombination implements Rule
{
    private ?string $listingPurpose;

    private ?string $unitStatus;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(array $data = [])
    {
        $this->listingPurpose = $data['listing_purpose']
            ?? PropertyStatusSyncService::resolveListingPurposeFromLegacy(
                $data['purpose'] ?? null,
                $data['property_status'] ?? null
            );

        $this->unitStatus = $data['unit_status']
            ?? PropertyStatusSyncService::resolveUnitStatusFromLegacy(
                $data['purpose'] ?? null,
                $data['property_status'] ?? null
            );
    }

    public function passes($attribute, $value): bool
    {
        if ($this->listingPurpose === null || $this->unitStatus === null) {
            return true;
        }

        if ($this->listingPurpose === 'sale' && $this->unitStatus === 'rented') {
            return false;
        }

        if ($this->listingPurpose === 'rent' && $this->unitStatus === 'sold') {
            return false;
        }

        return true;
    }

    public function message(): string
    {
        if ($this->listingPurpose === 'sale' && $this->unitStatus === 'rented') {
            return 'A sale listing cannot have unit status rented.';
        }

        if ($this->listingPurpose === 'rent' && $this->unitStatus === 'sold') {
            return 'A rent listing cannot have unit status sold.';
        }

        return 'The listing purpose and unit status combination is invalid.';
    }
}
