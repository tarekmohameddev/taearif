<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PropertyTypeRule implements ValidationRule
{
    /**
     * ============================================================
     * Section 1: property_type is nullable (optional, restricted)
     * ============================================================
     * Used by:
     * - POST /api/v1/property-requests/public
     * - POST /api/v1/property-requests
     * - GET  /api/v1/property-requests   (when treated as a strict type filter)
     * - POST /api/v1/admin/inquiries
     * - POST /api/v2/customers-hub/{requestId}/complete-data
     * - POST /api/v2/customers-hub/pipeline (filters.property_type.*)
     */
    public static function nullableRule(): array
    {
        return ['nullable', 'string', new self()];
    }

    /**
     * ============================================================
     * Section 2: property_type is required (not nullable, restricted)
     * ============================================================
     * Used by:
     * - POST /api/properties
     * - PUT  /api/properties
     */
    public static function requiredRule(): array
    {
        return ['required', 'string', new self()];
    }

    /**
     * ============================================================
     * Section 3: property_type is a free-string filter only
     * ============================================================
     * These endpoints use property_type only as a query filter and do not
     * restrict it to the four canonical values.
     *
     * Used by:
     * - GET /api/v1/inquiry
     * - GET /api/v1/admin/inquiries
     */
    public static function freeStringFilterRule(int $max = 255): array
    {
        return ['nullable', 'string', "max:{$max}"];
    }

    /**
     * ============================================================
     * Section 4: property_type is sometimes present and not restricted
     * ============================================================
     * This endpoint accepts property_type optionally with no value restriction.
     *
     * Used by:
     * - PUT /api/v1/admin/inquiries/{id}
     */
    public static function sometimesUnrestrictedRule(int $max = 100): array
    {
        return ['sometimes', 'nullable', 'string', "max:{$max}"];
    }

    /**
     * ============================================================
     * Section 5: property_type is set internally (not from request body)
     * ============================================================
     * Used by:
     * - POST /api/v1/property-requests/interest
     *
     * Note: The request does not accept property_type. The value is derived from
     * internal data and should be normalized + validated before saving.
     */

    /**
     * ============================================================
     * Section 6: property_type is read from external webhook payloads
     * ============================================================
     * Used by:
     * - POST /api/whatsapp/webhook
     * - POST /api/whatsapp/evolution-webhook
     *
     * Note: These are webhook endpoints; request validation is not enforced.
     * Normalize to lowercase before saving.
     */

    /**
     * ============================================================
     * Section 7: endpoint explicitly excludes property_type
     * ============================================================
     * Used by:
     * - PUT /api/v1/property-requests/{id}
     */

    /**
     * Canonical allowed values (always stored in lowercase).
     *
     * IMPORTANT: Arabic aliases are not supported.
     *
     * @return array<int, string>
     */
    public static function allowed(): array
    {
        return ['residential', 'commercial', 'agricultural', 'industrial'];
    }

    /**
     * Normalize any incoming value to canonical lowercase.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) return null;
        $value = trim($value);
        if ($value === '') return null;

        return strtolower($value);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        if (!is_string($value)) {
            $fail("The {$attribute} must be a string.");
            return;
        }

        $normalized = self::normalize($value);
        if ($normalized === null) {
            return;
        }

        if (!in_array($normalized, self::allowed(), true)) {
            $fail("The {$attribute} must be one of: " . implode(', ', self::allowed()) . '.');
        }
    }
}

