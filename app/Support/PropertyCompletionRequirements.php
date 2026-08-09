<?php

namespace App\Support;

/**
 * Single source of truth for the fields a property must have to be "complete".
 *
 * Deliberately a SUBSET of single create (POST /properties, PropertyStoreRequest),
 * which additionally requires slider_images, latitude/longitude, category_id,
 * city_id and an uploaded featured_image file. Excel import and draft-complete
 * accept URL/path images and do not require location, so they enforce these five.
 */
class PropertyCompletionRequirements
{
    /**
     * Canonical required field keys, in reporting order.
     *
     * @return array<int, string>
     */
    public static function fields(): array
    {
        return ['title', 'address', 'description', 'featured_image', 'property_type'];
    }

    /**
     * Map incoming aliases onto canonical keys (Excel/legacy `type` → `property_type`).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeInput(array $data): array
    {
        if (!array_key_exists('property_type', $data) && array_key_exists('type', $data)) {
            $data['property_type'] = $data['type'];
        }

        return $data;
    }

    /**
     * Required fields absent or blank in $data, as canonical English keys.
     *
     * Callers persist these into `missing_fields`; Arabic labels are applied at
     * the response layer, never stored.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public static function missingFrom(array $data): array
    {
        $data = self::normalizeInput($data);

        $missing = [];
        foreach (self::fields() as $field) {
            if (!self::valueProvided($data[$field] ?? null)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function isComplete(array $data): bool
    {
        return self::missingFrom($data) === [];
    }

    /**
     * A value counts as provided when it is not null, not '' and not an empty array.
     * Matches PropertiesSingleSheetImport::valueProvided() so import and API agree.
     */
    public static function valueProvided($value): bool
    {
        if (is_null($value)) {
            return false;
        }

        if (is_string($value)) {
            return trim($value) !== '';
        }

        if (is_array($value)) {
            return $value !== [];
        }

        return true;
    }
}
