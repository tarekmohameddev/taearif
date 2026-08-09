<?php

namespace App\Services;

use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Rules\PropertyTypeRule;
use App\Support\PropertyCompletionRequirements;

class PropertyConflictDetectionService
{
    /**
     * Find a complete property owned by the same user whose five completion-
     * identity fields match $data exactly (after canonicalize).
     *
     * Folding strategy:
     * - PHP: PropertyCompletionRequirements::canonicalize (trim, collapse
     *   internal whitespace, mb_strtolower) + PropertyTypeRule::normalize for type
     * - SQL: REGEXP_REPLACE(LOWER(TRIM(col)), '[[:space:]]+', ' ') compared to
     *   already-canonical bindings, so a DB value with irregular internal
     *   spacing still folds to the same key as the PHP side (MariaDB/MySQL 8+;
     *   plain LOWER(TRIM()) alone does not collapse mid-string whitespace).
     *
     * @param  int  $userId  Tenant owner id
     * @param  array<string, mixed>  $data
     * @param  int|null  $excludePropertyId  Property being completed, if any
     * @param  int|null  $languageId  Defaults to the owner's default language
     */
    public function findExactCompletionDuplicate(
        int $userId,
        array $data,
        ?int $excludePropertyId = null,
        ?int $languageId = null
    ): ?PropertyContent {
        $identity = PropertyCompletionRequirements::identityValues($data);
        if ($identity === null) {
            return null;
        }

        if ($languageId === null) {
            $languageId = Language::where('user_id', $userId)
                ->where('is_default', 1)
                ->value('id');
        }

        if ($languageId === null) {
            return null;
        }

        return PropertyContent::query()
            ->where('user_id', $userId)
            ->where('language_id', $languageId)
            ->whereRaw("REGEXP_REPLACE(LOWER(TRIM(title)), '[[:space:]]+', ' ') = ?", [$identity['title']])
            ->whereRaw("REGEXP_REPLACE(LOWER(TRIM(address)), '[[:space:]]+', ' ') = ?", [$identity['address']])
            ->whereRaw("REGEXP_REPLACE(LOWER(TRIM(description)), '[[:space:]]+', ' ') = ?", [$identity['description']])
            ->whereHas('property', function ($q) use ($excludePropertyId, $identity) {
                $q->where('completion_status', 'complete')
                    ->whereRaw("REGEXP_REPLACE(LOWER(TRIM(featured_image)), '[[:space:]]+', ' ') = ?", [$identity['featured_image']])
                    ->whereRaw("REGEXP_REPLACE(LOWER(TRIM(property_type)), '[[:space:]]+', ' ') = ?", [$identity['property_type']]);

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

        // Exact match on all five completion-identity fields vs a complete peer
        $duplicate = $this->findExactCompletionDuplicate(
            $property->user_id,
            $data,
            $property->id
        );

        if ($duplicate) {
            $conflicts[] = [
                'type' => 'duplicate',
                'field' => 'completion_identity',
                'message' => 'A property with the same title, address, description, featured image, and property type already exists',
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
