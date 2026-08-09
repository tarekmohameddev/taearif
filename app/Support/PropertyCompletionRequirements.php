<?php

namespace App\Support;

use App\Rules\PropertyTypeRule;

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
     * Fold a string for exact-identity comparison: trim, collapse internal
     * whitespace, then lowercase.
     */
    public static function canonicalize(string $v): string
    {
        $v = trim($v);
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return mb_strtolower($v);
    }

    /**
     * Canonical five identity values, or null if any field is blank / un-normalizable.
     *
     * @param  array<string, mixed>  $data
     * @return array{title: string, address: string, description: string, featured_image: string, property_type: string}|null
     */
    public static function identityValues(array $data): ?array
    {
        $data = self::normalizeInput($data);
        $values = [];

        foreach (self::fields() as $field) {
            $raw = $data[$field] ?? null;

            if ($field === 'property_type') {
                $normalized = is_string($raw) ? PropertyTypeRule::normalize($raw) : null;
                if ($normalized === null) {
                    return null;
                }
                $values[$field] = self::canonicalize($normalized);
                continue;
            }

            if (!self::valueProvided($raw)) {
                return null;
            }

            $values[$field] = self::canonicalize((string) $raw);
        }

        return $values;
    }

    /**
     * Stable hashable key for the five identity values (tests / optional in-memory sets).
     *
     * @param  array{title: string, address: string, description: string, featured_image: string, property_type: string}|null  $values
     */
    public static function identityKey(?array $values): ?string
    {
        if ($values === null) {
            return null;
        }

        return implode("\x1f", [
            $values['title'],
            $values['address'],
            $values['description'],
            $values['featured_image'],
            $values['property_type'],
        ]);
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
