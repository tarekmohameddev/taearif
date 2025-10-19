<?php

namespace App\Constants;

/**
 * Error Codes
 *
 * Centralized error codes for consistent error handling across the application
 *
 * Format: DOMAIN_ENTITY_ERROR
 * Categories:
 * - 1xxx: Validation errors
 * - 2xxx: Resource not found errors
 * - 3xxx: Authorization errors
 * - 4xxx: Business logic errors
 * - 5xxx: Server errors
 */
class ErrorCodes
{
    // ============================================
    // General Errors (0-999)
    // ============================================
    public const VALIDATION_FAILED = 'VALIDATION_FAILED';
    public const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    public const UNAUTHORIZED = 'UNAUTHORIZED';
    public const FORBIDDEN = 'FORBIDDEN';
    public const SERVER_ERROR = 'SERVER_ERROR';
    public const BUSINESS_LOGIC_ERROR = 'BUSINESS_LOGIC_ERROR';

    // ============================================
    // RMS - Rental Errors (1000-1099)
    // ============================================
    public const RMS_RENTAL_NOT_FOUND = 'RMS_RENTAL_NOT_FOUND';
    public const RMS_RENTAL_NOT_OWNED = 'RMS_RENTAL_NOT_OWNED';
    public const RMS_RENTAL_HAS_ACTIVE_CONTRACT = 'RMS_RENTAL_HAS_ACTIVE_CONTRACT';
    public const RMS_RENTAL_INVALID_STATUS_TRANSITION = 'RMS_RENTAL_INVALID_STATUS_TRANSITION';
    public const RMS_RENTAL_CANNOT_MODIFY_ENDED = 'RMS_RENTAL_CANNOT_MODIFY_ENDED';
    public const RMS_PROPERTY_ALREADY_RENTED = 'RMS_PROPERTY_ALREADY_RENTED';

    // ============================================
    // RMS - Contract Errors (1100-1199)
    // ============================================
    public const RMS_CONTRACT_NOT_FOUND = 'RMS_CONTRACT_NOT_FOUND';
    public const RMS_CONTRACT_ALREADY_ACTIVE = 'RMS_CONTRACT_ALREADY_ACTIVE';
    public const RMS_CONTRACT_ALREADY_TERMINATED = 'RMS_CONTRACT_ALREADY_TERMINATED';
    public const RMS_CONTRACT_CANNOT_MODIFY = 'RMS_CONTRACT_CANNOT_MODIFY';
    public const RMS_CONTRACT_INVALID_STATUS = 'RMS_CONTRACT_INVALID_STATUS';
    public const RMS_CONTRACT_GRACE_PERIOD_EXCEEDED = 'RMS_CONTRACT_GRACE_PERIOD_EXCEEDED';

    // ============================================
    // RMS - Payment/Installment Errors (1200-1299)
    // ============================================
    public const RMS_INSTALLMENT_NOT_FOUND = 'RMS_INSTALLMENT_NOT_FOUND';
    public const RMS_INSTALLMENT_ALREADY_PAID = 'RMS_INSTALLMENT_ALREADY_PAID';
    public const RMS_PAYMENT_AMOUNT_EXCEEDS_DUE = 'RMS_PAYMENT_AMOUNT_EXCEEDS_DUE';
    public const RMS_PAYMENT_FAILED = 'RMS_PAYMENT_FAILED';

    // ============================================
    // RMS - Maintenance Errors (1300-1399)
    // ============================================
    public const RMS_MAINTENANCE_NOT_FOUND = 'RMS_MAINTENANCE_NOT_FOUND';
    public const RMS_MAINTENANCE_INVALID_STATUS = 'RMS_MAINTENANCE_INVALID_STATUS';

    // ============================================
    // Property Errors (2000-2099)
    // ============================================
    public const PROPERTY_NOT_FOUND = 'PROPERTY_NOT_FOUND';
    public const PROPERTY_NOT_OWNED = 'PROPERTY_NOT_OWNED';
    public const PROPERTY_LIMIT_REACHED = 'PROPERTY_LIMIT_REACHED';
    public const PROPERTY_ALREADY_RENTED = 'PROPERTY_ALREADY_RENTED';
    public const PROPERTY_INVALID_TYPE = 'PROPERTY_INVALID_TYPE';

    // ============================================
    // Project Errors (2100-2199)
    // ============================================
    public const PROJECT_NOT_FOUND = 'PROJECT_NOT_FOUND';
    public const PROJECT_NOT_OWNED = 'PROJECT_NOT_OWNED';
    public const PROJECT_LIMIT_REACHED = 'PROJECT_LIMIT_REACHED';

    // ============================================
    // CRM - Customer Errors (3000-3099)
    // ============================================
    public const CRM_CUSTOMER_NOT_FOUND = 'CRM_CUSTOMER_NOT_FOUND';
    public const CRM_CUSTOMER_NOT_OWNED = 'CRM_CUSTOMER_NOT_OWNED';
    public const CRM_CUSTOMER_DUPLICATE = 'CRM_CUSTOMER_DUPLICATE';
    public const CRM_CUSTOMER_INVALID_STAGE_TRANSITION = 'CRM_CUSTOMER_INVALID_STAGE_TRANSITION';

    // ============================================
    // CRM - Card Errors (3100-3199)
    // ============================================
    public const CRM_CARD_NOT_FOUND = 'CRM_CARD_NOT_FOUND';
    public const CRM_CARD_NOT_OWNED = 'CRM_CARD_NOT_OWNED';

    // ============================================
    // Payment Errors (4000-4099)
    // ============================================
    public const PAYMENT_FAILED = 'PAYMENT_FAILED';
    public const PAYMENT_GATEWAY_ERROR = 'PAYMENT_GATEWAY_ERROR';
    public const PAYMENT_INSUFFICIENT_CREDITS = 'PAYMENT_INSUFFICIENT_CREDITS';
    public const PAYMENT_INVALID_AMOUNT = 'PAYMENT_INVALID_AMOUNT';

    // ============================================
    // Auth Errors (5000-5099)
    // ============================================
    public const AUTH_INVALID_CREDENTIALS = 'AUTH_INVALID_CREDENTIALS';
    public const AUTH_TOKEN_EXPIRED = 'AUTH_TOKEN_EXPIRED';
    public const AUTH_TOKEN_INVALID = 'AUTH_TOKEN_INVALID';
    public const AUTH_USER_NOT_FOUND = 'AUTH_USER_NOT_FOUND';

    // ============================================
    // Building Errors (6000-6099)
    // ============================================
    public const BUILDING_NOT_FOUND = 'BUILDING_NOT_FOUND';
    public const BUILDING_HAS_PROPERTIES = 'BUILDING_HAS_PROPERTIES';

    /**
     * Get all error codes as array
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);
        return $reflection->getConstants();
    }

    /**
     * Check if error code exists
     */
    public static function isValid(string $code): bool
    {
        return in_array($code, self::all());
    }
}

