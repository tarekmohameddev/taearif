<?php

namespace App\Services;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Rules\PropertyTypeRule;
use App\Support\PropertyCompletionRequirements;
use Illuminate\Support\Facades\DB;

class PropertyConflictDetectionService
{
    /**
     * Find a complete property owned by the same user that already uses this
     * title + address pair. Incomplete drafts are not peers — only completed
     * listings can be duplicated.
     *
     * @param  int  $userId  Tenant owner id
     * @param  int|null  $excludePropertyId  Property being completed, if any
     */
    public function findTitleAddressDuplicate(
        int $userId,
        ?string $title,
        ?string $address,
        ?int $excludePropertyId = null
    ): ?PropertyContent {
        if (!PropertyCompletionRequirements::valueProvided($title)
            || !PropertyCompletionRequirements::valueProvided($address)) {
            return null;
        }

        return PropertyContent::where('title', $title)
            ->where('address', $address)
            ->whereHas('property', function ($q) use ($userId, $excludePropertyId) {
                $q->where('user_id', $userId)
                  ->where('completion_status', 'complete');

                if ($excludePropertyId !== null) {
                    $q->where('id', '!=', $excludePropertyId);
                }
            })
            ->first();
    }

    /**
     * Check for conflicts before completing a property
     *
     * @param Property $property
     * @param array $data
     * @return array Array of conflicts with severity levels
     */
    public function detectConflicts(Property $property, array $data): array
    {
        $conflicts = [];
        $data = PropertyCompletionRequirements::normalizeInput($data);

        // Check for duplicate title + address combination
        $duplicate = $this->findTitleAddressDuplicate(
            $property->user_id,
            $data['title'] ?? null,
            $data['address'] ?? null,
            $property->id
        );

        if ($duplicate) {
            $conflicts[] = [
                'type' => 'duplicate',
                'field' => 'title+address',
                'message' => 'A property with the same title and address already exists',
                'severity' => 'error'
            ];
        }

        // Validate required fields are present (shared five-field definition)
        $missing = PropertyCompletionRequirements::missingFrom($data);

        if (!empty($missing)) {
            $conflicts[] = [
                'type' => 'missing_fields',
                'fields' => $missing,
                'message' => 'Required fields are missing: ' . implode(', ', $missing),
                'severity' => 'error'
            ];
        }
        
        // Validate data formats.
        // price / purpose / area are optional for completeness — only their
        // FORMAT is checked, and only when a value was actually supplied.
        // An empty string is "not supplied", not "invalid".
        if (PropertyCompletionRequirements::valueProvided($data['price'] ?? null)
            && (!is_numeric($data['price']) || $data['price'] < 0)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'price',
                'message' => 'Price must be a positive number',
                'severity' => 'error'
            ];
        }

        if (PropertyCompletionRequirements::valueProvided($data['area'] ?? null)
            && (!is_numeric($data['area']) || $data['area'] < 1)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'area',
                'message' => 'Area must be at least 1',
                'severity' => 'error'
            ];
        }

        if (PropertyCompletionRequirements::valueProvided($data['purpose'] ?? null)
            && !in_array($data['purpose'], ['sale', 'rent'])) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'purpose',
                'message' => 'Purpose must be either "sale" or "rent"',
                'severity' => 'error'
            ];
        }

        $allowedTypes = PropertyTypeRule::allowed();
        $rawType = $data['property_type'] ?? null;
        $propertyType = is_string($rawType) ? PropertyTypeRule::normalize($rawType) : null;
        if ($propertyType !== null && !in_array($propertyType, $allowedTypes, true)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'property_type',
                'message' => 'Property type must be one of: ' . implode(', ', $allowedTypes),
                'severity' => 'error'
            ];
        }

        // Length checks run only on supplied values — a blank required field is
        // already reported once as missing_fields and must not error twice.
        if (PropertyCompletionRequirements::valueProvided($data['title'] ?? null)
            && (mb_strlen($data['title']) < 3 || mb_strlen($data['title']) > 255)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'title',
                'message' => 'Title must be between 3 and 255 characters',
                'severity' => 'error'
            ];
        }

        if (PropertyCompletionRequirements::valueProvided($data['address'] ?? null)
            && (mb_strlen($data['address']) < 5 || mb_strlen($data['address']) > 500)) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'address',
                'message' => 'Address must be between 5 and 500 characters',
                'severity' => 'error'
            ];
        }

        if (PropertyCompletionRequirements::valueProvided($data['description'] ?? null)
            && mb_strlen($data['description']) < 10) {
            $conflicts[] = [
                'type' => 'validation',
                'field' => 'description',
                'message' => 'Description must be at least 10 characters',
                'severity' => 'error'
            ];
        }

        return $conflicts;
    }
    
    /**
     * Check if property can be completed (no errors)
     * 
     * @param Property $property
     * @param array $data
     * @return bool
     */
    public function canComplete(Property $property, array $data): bool
    {
        $conflicts = $this->detectConflicts($property, $data);
        
        // Only allow completion if there are no errors
        foreach ($conflicts as $conflict) {
            if ($conflict['severity'] === 'error') {
                return false;
            }
        }
        
        return true;
    }
}
