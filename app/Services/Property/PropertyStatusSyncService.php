<?php

namespace App\Services\Property;

use App\Models\User\RealestateManagement\Property;

class PropertyStatusSyncService
{
    private static bool $syncing = false;

    /**
     * @param  array<string, mixed>  $data
     */
    public function syncArray(array &$data, bool $newFieldsTakePrecedence = true): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        try {
            $hasNew = $this->hasPresent($data, ['listing_purpose', 'unit_status', 'publish_status']);
            $hasLegacy = $this->hasPresent($data, ['purpose', 'property_status', 'status', 'completion_status']);

            if ($hasNew && ($newFieldsTakePrecedence || ! $hasLegacy)) {
                $this->applyNewToLegacyArray($data);
            } elseif ($hasLegacy) {
                $this->applyLegacyToNewArray($data);
            }
        } finally {
            self::$syncing = false;
        }
    }

    public function syncModel(Property $property): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        try {
            $dirty = $property->getDirty();
            $newDirty = array_intersect_key($dirty, array_flip(['listing_purpose', 'unit_status', 'publish_status']));
            $legacyDirty = array_intersect_key($dirty, array_flip(['purpose', 'property_status', 'status', 'completion_status']));

            if (! empty($newDirty)) {
                $this->applyNewToLegacyModel($property);
            } elseif (! empty($legacyDirty) || ! $property->exists) {
                $this->applyLegacyToNewModel($property);
            }
        } finally {
            self::$syncing = false;
        }
    }

    public static function resolveListingPurposeFromLegacy(?string $purpose, ?string $propertyStatus): ?string
    {
        if (in_array($purpose, ['sale', 'rent'], true)) {
            return $purpose;
        }

        if ($purpose === 'sold') {
            return 'sale';
        }

        if ($purpose === 'rented') {
            return 'rent';
        }

        if (in_array($propertyStatus, ['for_sale', 'sale'], true)) {
            return 'sale';
        }

        if ($propertyStatus === 'for_rent') {
            return 'rent';
        }

        return null;
    }

    public static function resolveUnitStatusFromLegacy(?string $purpose, ?string $propertyStatus): string
    {
        if ($purpose === 'sold') {
            return 'sold';
        }

        if ($purpose === 'rented') {
            return 'rented';
        }

        if ($propertyStatus === 'rented') {
            return 'rented';
        }

        if ($propertyStatus === 'available') {
            return 'available';
        }

        if (in_array($propertyStatus, ['for_sale', 'for_rent', 'sale'], true)) {
            return 'available';
        }

        return 'available';
    }

    public static function resolvePublishStatusFromLegacy(?string $completionStatus, $status): string
    {
        if ($completionStatus === 'complete' && (int) $status === 1) {
            return 'published';
        }

        return 'draft';
    }

    public static function unitStatusToPropertyStatus(?string $unitStatus): ?string
    {
        return match ($unitStatus) {
            'available', 'reserved' => 'available',
            'sold' => 'sale',
            'rented' => 'rented',
            default => null,
        };
    }

    public static function normalizeLegacyPurpose(?string $purpose): ?string
    {
        return match ($purpose) {
            'sold' => 'sale',
            'rented' => 'rent',
            default => $purpose,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function hasPresent(array $data, array $keys): bool
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyNewToLegacyArray(array &$data): void
    {
        if (! empty($data['listing_purpose'])) {
            $data['purpose'] = $data['listing_purpose'];
        }

        if (! empty($data['unit_status'])) {
            $mapped = self::unitStatusToPropertyStatus($data['unit_status']);
            if ($mapped !== null) {
                $data['property_status'] = $mapped;
            }
        }

        if (! empty($data['publish_status'])) {
            $data['status'] = $data['publish_status'] === 'published' ? 1 : 0;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyLegacyToNewArray(array &$data): void
    {
        $purpose = $data['purpose'] ?? null;
        $propertyStatus = $data['property_status'] ?? null;

        if (empty($data['listing_purpose'])) {
            $data['listing_purpose'] = self::resolveListingPurposeFromLegacy($purpose, $propertyStatus);
        }

        if (empty($data['unit_status'])) {
            $data['unit_status'] = self::resolveUnitStatusFromLegacy($purpose, $propertyStatus);
        }

        if (empty($data['publish_status'])) {
            $data['publish_status'] = self::resolvePublishStatusFromLegacy(
                $data['completion_status'] ?? null,
                $data['status'] ?? 0
            );
        }

        if (in_array($purpose, ['sold', 'rented'], true)) {
            $data['purpose'] = self::normalizeLegacyPurpose($purpose);
        }

        if (! empty($data['listing_purpose'])) {
            $data['purpose'] = $data['listing_purpose'];
        }

        if (! empty($data['unit_status'])) {
            $mapped = self::unitStatusToPropertyStatus($data['unit_status']);
            if ($mapped !== null) {
                $data['property_status'] = $mapped;
            }
        }

        if (! empty($data['publish_status'])) {
            $data['status'] = $data['publish_status'] === 'published' ? 1 : 0;
        }
    }

    private function applyNewToLegacyModel(Property $property): void
    {
        if ($property->listing_purpose !== null && $property->listing_purpose !== '') {
            $property->purpose = $property->listing_purpose;
        }

        if ($property->unit_status !== null && $property->unit_status !== '') {
            $mapped = self::unitStatusToPropertyStatus($property->unit_status);
            if ($mapped !== null) {
                $property->property_status = $mapped;
            }
        }

        if ($property->publish_status !== null && $property->publish_status !== '') {
            $property->status = $property->publish_status === 'published' ? 1 : 0;
        }
    }

    private function applyLegacyToNewModel(Property $property): void
    {
        $property->listing_purpose = self::resolveListingPurposeFromLegacy(
            $property->purpose,
            $property->property_status
        );

        $property->unit_status = self::resolveUnitStatusFromLegacy(
            $property->purpose,
            $property->property_status
        );

        $property->publish_status = self::resolvePublishStatusFromLegacy(
            $property->completion_status,
            $property->status
        );

        if (in_array($property->purpose, ['sold', 'rented'], true)) {
            $property->purpose = self::normalizeLegacyPurpose($property->purpose);
        }

        if ($property->listing_purpose !== null && $property->listing_purpose !== '') {
            $property->purpose = $property->listing_purpose;
        }

        if ($property->unit_status !== null && $property->unit_status !== '') {
            $mapped = self::unitStatusToPropertyStatus($property->unit_status);
            if ($mapped !== null) {
                $property->property_status = $mapped;
            }
        }

        if ($property->publish_status !== null && $property->publish_status !== '') {
            $property->status = $property->publish_status === 'published' ? 1 : 0;
        }
    }
}
