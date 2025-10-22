<?php

namespace App\Constants;

/**
 * RMS (Rental Management System) Constants
 *
 * Centralized constants for the RMS module to eliminate hardcoded values
 * and ensure consistency across the application.
 *
 * Usage in validation rules:
 * ```php
 * 'status' => ['required', RmsConstants::validationRule(RmsConstants::RENTAL_STATUSES)]
 * // Or use in() method:
 * 'status' => ['required', Rule::in(RmsConstants::RENTAL_STATUSES)]
 * ```
 *
 * Usage in code:
 * ```php
 * if ($rental->status === RmsConstants::RENTAL_STATUS_ACTIVE) {
 *     // Do something
 * }
 * ```
 */
class RmsConstants
{
    // ========================================
    // RENTAL TYPES
    // ========================================

    const RENTAL_TYPE_MONTHLY = 'monthly';
    const RENTAL_TYPE_ANNUAL = 'annual';

    const RENTAL_TYPES = [
        self::RENTAL_TYPE_MONTHLY,
        self::RENTAL_TYPE_ANNUAL,
    ];

    // ========================================
    // PAYING PLANS (Payment Frequency)
    // ========================================

    const PAYING_PLAN_MONTHLY = 'monthly';
    const PAYING_PLAN_QUARTERLY = 'quarterly';
    const PAYING_PLAN_SEMI_ANNUAL = 'semi_annual';
    const PAYING_PLAN_ANNUAL = 'annual';

    const PAYING_PLANS = [
        self::PAYING_PLAN_MONTHLY,
        self::PAYING_PLAN_QUARTERLY,
        self::PAYING_PLAN_SEMI_ANNUAL,
        self::PAYING_PLAN_ANNUAL,
    ];

    // ========================================
    // CONTRACT STATUSES
    // ========================================

    const CONTRACT_STATUS_PENDING = 'pending';
    const CONTRACT_STATUS_ACTIVE = 'active';
    const CONTRACT_STATUS_EXPIRED = 'expired';
    const CONTRACT_STATUS_TERMINATED = 'terminated';

    const CONTRACT_STATUSES = [
        self::CONTRACT_STATUS_PENDING,
        self::CONTRACT_STATUS_ACTIVE,
        self::CONTRACT_STATUS_EXPIRED,
        self::CONTRACT_STATUS_TERMINATED,
    ];

    // ========================================
    // RENTAL STATUSES
    // ========================================

    const RENTAL_STATUS_ACTIVE = 'active';
    const RENTAL_STATUS_INACTIVE = 'inactive';
    const RENTAL_STATUS_TERMINATED = 'terminated';
    const RENTAL_STATUS_ENDED = 'ended';
    const RENTAL_STATUS_CANCELLED = 'cancelled';
    const RENTAL_STATUS_DRAFT = 'draft';

    const RENTAL_STATUSES = [
        self::RENTAL_STATUS_ACTIVE,
        self::RENTAL_STATUS_INACTIVE,
        self::RENTAL_STATUS_TERMINATED,
        self::RENTAL_STATUS_ENDED,
        self::RENTAL_STATUS_CANCELLED,
        self::RENTAL_STATUS_DRAFT,
    ];

    // ========================================
    // PAYMENT METHODS
    // ========================================

    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_BANK_TRANSFER = 'bank_transfer';
    const PAYMENT_METHOD_CREDIT_CARD = 'credit_card';
    const PAYMENT_METHOD_ONLINE_PAYMENT = 'online_payment';
    const PAYMENT_METHOD_CHECK = 'check';
    const PAYMENT_METHOD_OTHER = 'other';

    const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_CASH,
        self::PAYMENT_METHOD_BANK_TRANSFER,
        self::PAYMENT_METHOD_CREDIT_CARD,
        self::PAYMENT_METHOD_ONLINE_PAYMENT,
        self::PAYMENT_METHOD_CHECK,
        self::PAYMENT_METHOD_OTHER,
    ];

    // ========================================
    // PAYMENT TYPES
    // ========================================

    const PAYMENT_TYPE_RENT = 'rent';
    const PAYMENT_TYPE_COST_ITEM = 'cost_item';
    const PAYMENT_TYPE_DEPOSIT = 'deposit';

    const PAYMENT_TYPES = [
        self::PAYMENT_TYPE_RENT,
        self::PAYMENT_TYPE_COST_ITEM,
        self::PAYMENT_TYPE_DEPOSIT,
    ];

    // ========================================
    // TRANSFER TO OPTIONS (Arabic)
    // ========================================

    const TRANSFER_TO_NAJIZ = 'منصة ناجز';
    const TRANSFER_TO_OWNER = 'المالك';
    const TRANSFER_TO_OFFICE = 'المكتب';

    const TRANSFER_TO_OPTIONS = [
        self::TRANSFER_TO_NAJIZ,
        self::TRANSFER_TO_OWNER,
        self::TRANSFER_TO_OFFICE,
    ];

    // ========================================
    // COST CENTERS
    // ========================================

    const COST_CENTER_TENANT = 'tenant';
    const COST_CENTER_OWNER = 'owner';

    const COST_CENTERS = [
        self::COST_CENTER_TENANT,
        self::COST_CENTER_OWNER,
    ];

    // ========================================
    // COST ITEM PAYERS
    // ========================================

    const PAYER_OWNER = 'owner';
    const PAYER_TENANT = 'tenant';

    const PAYERS = [
        self::PAYER_OWNER,
        self::PAYER_TENANT,
    ];

    // ========================================
    // COST ITEM TYPES
    // ========================================

    const COST_ITEM_TYPE_FIXED = 'fixed';
    const COST_ITEM_TYPE_PERCENTAGE = 'percentage';

    const COST_ITEM_TYPES = [
        self::COST_ITEM_TYPE_FIXED,
        self::COST_ITEM_TYPE_PERCENTAGE,
    ];

    // ========================================
    // PAYMENT FREQUENCIES
    // ========================================

    const PAYMENT_FREQUENCY_ONE_TIME = 'one_time';
    const PAYMENT_FREQUENCY_PER_INSTALLMENT = 'per_installment';

    const PAYMENT_FREQUENCIES = [
        self::PAYMENT_FREQUENCY_ONE_TIME,
        self::PAYMENT_FREQUENCY_PER_INSTALLMENT,
    ];

    // ========================================
    // INSTALLMENT STATUSES
    // ========================================

    const INSTALLMENT_STATUS_PENDING = 'pending';
    const INSTALLMENT_STATUS_PAID = 'paid';
    const INSTALLMENT_STATUS_PARTIAL = 'partial';
    const INSTALLMENT_STATUS_OVERDUE = 'overdue';
    const INSTALLMENT_STATUS_VOID = 'void';

    const INSTALLMENT_STATUSES = [
        self::INSTALLMENT_STATUS_PENDING,
        self::INSTALLMENT_STATUS_PAID,
        self::INSTALLMENT_STATUS_PARTIAL,
        self::INSTALLMENT_STATUS_OVERDUE,
        self::INSTALLMENT_STATUS_VOID,
    ];

    // ========================================
    // MAINTENANCE PRIORITIES
    // ========================================

    const MAINTENANCE_PRIORITY_LOW = 'low';
    const MAINTENANCE_PRIORITY_MEDIUM = 'medium';
    const MAINTENANCE_PRIORITY_HIGH = 'high';
    const MAINTENANCE_PRIORITY_CRITICAL = 'critical';

    const MAINTENANCE_PRIORITIES = [
        self::MAINTENANCE_PRIORITY_LOW,
        self::MAINTENANCE_PRIORITY_MEDIUM,
        self::MAINTENANCE_PRIORITY_HIGH,
        self::MAINTENANCE_PRIORITY_CRITICAL,
    ];

    // ========================================
    // MAINTENANCE STATUSES
    // ========================================

    const MAINTENANCE_STATUS_OPEN = 'open';
    const MAINTENANCE_STATUS_IN_PROGRESS = 'in_progress';
    const MAINTENANCE_STATUS_ON_HOLD = 'on_hold';
    const MAINTENANCE_STATUS_RESOLVED = 'resolved';
    const MAINTENANCE_STATUS_CANCELLED = 'cancelled';

    const MAINTENANCE_STATUSES = [
        self::MAINTENANCE_STATUS_OPEN,
        self::MAINTENANCE_STATUS_IN_PROGRESS,
        self::MAINTENANCE_STATUS_ON_HOLD,
        self::MAINTENANCE_STATUS_RESOLVED,
        self::MAINTENANCE_STATUS_CANCELLED,
    ];

    // ========================================
    // MAINTENANCE PAYERS
    // ========================================

    const MAINTENANCE_PAYER_LANDLORD = 'landlord';
    const MAINTENANCE_PAYER_TENANT = 'tenant';
    const MAINTENANCE_PAYER_SHARED = 'shared';

    const MAINTENANCE_PAYERS = [
        self::MAINTENANCE_PAYER_LANDLORD,
        self::MAINTENANCE_PAYER_TENANT,
        self::MAINTENANCE_PAYER_SHARED,
    ];

    // ========================================
    // SOCIAL STATUSES
    // ========================================

    const SOCIAL_STATUS_SINGLE = 'single';
    const SOCIAL_STATUS_MARRIED = 'married';
    const SOCIAL_STATUS_DIVORCED = 'divorced';
    const SOCIAL_STATUS_WIDOWED = 'widowed';
    const SOCIAL_STATUS_OTHER = 'other';

    const SOCIAL_STATUSES = [
        self::SOCIAL_STATUS_SINGLE,
        self::SOCIAL_STATUS_MARRIED,
        self::SOCIAL_STATUS_DIVORCED,
        self::SOCIAL_STATUS_WIDOWED,
        self::SOCIAL_STATUS_OTHER,
    ];

    // ========================================
    // AMOUNT TYPES
    // ========================================

    const AMOUNT_TYPE_PERCENTAGE = 'percentage';
    const AMOUNT_TYPE_FIXED = 'fixed';

    const AMOUNT_TYPES = [
        self::AMOUNT_TYPE_PERCENTAGE,
        self::AMOUNT_TYPE_FIXED,
    ];

    // ========================================
    // SORT FIELDS (for filtering/sorting)
    // ========================================

    const SORT_FIELD_CREATED_AT = 'created_at';
    const SORT_FIELD_UPDATED_AT = 'updated_at';
    const SORT_FIELD_MOVE_IN_DATE = 'move_in_date';
    const SORT_FIELD_TENANT_NAME = 'tenant_full_name';
    const SORT_FIELD_BASE_RENT = 'base_rent_amount';
    const SORT_FIELD_STATUS = 'status';

    const SORT_FIELDS = [
        self::SORT_FIELD_CREATED_AT,
        self::SORT_FIELD_UPDATED_AT,
        self::SORT_FIELD_MOVE_IN_DATE,
        self::SORT_FIELD_TENANT_NAME,
        self::SORT_FIELD_BASE_RENT,
        self::SORT_FIELD_STATUS,
    ];

    // ========================================
    // SORT ORDERS
    // ========================================

    const SORT_ORDER_ASC = 'asc';
    const SORT_ORDER_DESC = 'desc';

    const SORT_ORDERS = [
        self::SORT_ORDER_ASC,
        self::SORT_ORDER_DESC,
    ];

    // ========================================
    // PAYMENT COLLECTION STATUSES
    // ========================================

    const PAYMENT_COLLECTION_STATUS_OVERDUE = 'overdue';
    const PAYMENT_COLLECTION_STATUS_DUE_TODAY = 'due_today';
    const PAYMENT_COLLECTION_STATUS_UPCOMING = 'upcoming';

    const PAYMENT_COLLECTION_STATUSES = [
        self::PAYMENT_COLLECTION_STATUS_OVERDUE,
        self::PAYMENT_COLLECTION_STATUS_DUE_TODAY,
        self::PAYMENT_COLLECTION_STATUS_UPCOMING,
    ];

    // ========================================
    // HELPER METHODS
    // ========================================

    /**
     * Get validation rule for a constant array
     * Returns string that can be used in Laravel validation
     *
     * @param array $constants Array of constant values
     * @return string Validation rule string
     *
     * @example
     * RmsConstants::validationRule(RmsConstants::RENTAL_STATUSES)
     * // Returns: "in:active,inactive,terminated,ended,cancelled,draft"
     */
    public static function validationRule(array $constants): string
    {
        return 'in:' . implode(',', $constants);
    }

    /**
     * Check if a value is valid for a given constant array
     *
     * @param mixed $value Value to check
     * @param array $constants Array of valid constants
     * @return bool
     *
     * @example
     * RmsConstants::isValid('active', RmsConstants::RENTAL_STATUSES) // true
     * RmsConstants::isValid('invalid', RmsConstants::RENTAL_STATUSES) // false
     */
    public static function isValid($value, array $constants): bool
    {
        return in_array($value, $constants, true);
    }

    /**
     * Get all rental statuses as associative array
     * Useful for dropdowns or selection lists
     *
     * @return array
     */
    public static function getRentalStatusesArray(): array
    {
        return array_combine(self::RENTAL_STATUSES, self::RENTAL_STATUSES);
    }

    /**
     * Get all payment methods as associative array
     *
     * @return array
     */
    public static function getPaymentMethodsArray(): array
    {
        return array_combine(self::PAYMENT_METHODS, self::PAYMENT_METHODS);
    }

    /**
     * Get all maintenance priorities as associative array
     *
     * @return array
     */
    public static function getMaintenancePrioritiesArray(): array
    {
        return array_combine(self::MAINTENANCE_PRIORITIES, self::MAINTENANCE_PRIORITIES);
    }

    /**
     * Get all constants as associative array
     * Useful for API documentation or debugging
     *
     * @return array
     */
    public static function toArray(): array
    {
        return [
            'rental_types' => self::RENTAL_TYPES,
            'paying_plans' => self::PAYING_PLANS,
            'contract_statuses' => self::CONTRACT_STATUSES,
            'rental_statuses' => self::RENTAL_STATUSES,
            'payment_methods' => self::PAYMENT_METHODS,
            'payment_types' => self::PAYMENT_TYPES,
            'transfer_to_options' => self::TRANSFER_TO_OPTIONS,
            'cost_centers' => self::COST_CENTERS,
            'payers' => self::PAYERS,
            'cost_item_types' => self::COST_ITEM_TYPES,
            'payment_frequencies' => self::PAYMENT_FREQUENCIES,
            'installment_statuses' => self::INSTALLMENT_STATUSES,
            'maintenance_priorities' => self::MAINTENANCE_PRIORITIES,
            'maintenance_statuses' => self::MAINTENANCE_STATUSES,
            'maintenance_payers' => self::MAINTENANCE_PAYERS,
            'social_statuses' => self::SOCIAL_STATUSES,
            'amount_types' => self::AMOUNT_TYPES,
            'sort_fields' => self::SORT_FIELDS,
            'sort_orders' => self::SORT_ORDERS,
            'payment_collection_statuses' => self::PAYMENT_COLLECTION_STATUSES,
        ];
    }

    /**
     * Get constants by category
     *
     * @param string $category Category name
     * @return array|null
     */
    public static function getByCategory(string $category): ?array
    {
        $all = self::toArray();
        return $all[$category] ?? null;
    }
}

