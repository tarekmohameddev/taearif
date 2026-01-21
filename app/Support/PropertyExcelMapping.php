<?php

namespace App\Support;

/**
 * Maps property purpose and type between database (English) and Excel (Arabic).
 * Keeps DB values stable while allowing Arabic in exports/imports.
 * Single source of truth: add new purpose/type here, then in export/import validation.
 */
class PropertyExcelMapping
{
    /** DB (purpose) → Excel (Arabic) */
    public const PURPOSE_TO_EXCEL = [
        'sale' => 'بيع',
        'rent' => 'إيجار',
    ];

    /** DB (type) → Excel (Arabic) */
    public const TYPE_TO_EXCEL = [
        'residential' => 'سكني',
        'commercial' => 'تجاري',
    ];

    /** Excel (Arabic or English) → DB (purpose) */
    public const PURPOSE_TO_DB = [
        'بيع' => 'sale',
        'إيجار' => 'rent',
        'sale' => 'sale',
        'rent' => 'rent',
    ];

    /** Excel (Arabic or English) → DB (type) */
    public const TYPE_TO_DB = [
        'سكني' => 'residential',
        'تجاري' => 'commercial',
        'residential' => 'residential',
        'commercial' => 'commercial',
    ];

    /** Lookup sheet title for City-District Reference (used in title() and setFormula1) */
    public const LOOKUP_SHEET_TITLE = 'مرجع المدن والأحياء';

    /** DB (property_status) → Excel (Arabic) */
    public const PROPERTY_STATUS_TO_EXCEL = [
        'available' => 'متاح',
        'rented' => 'مُؤجّر',
        'for_rent' => 'للإيجار',
        'for_sale' => 'للبيع',
        'sale' => 'للبيع',
    ];

    /** Excel (Arabic or English) → DB (property_status) */
    public const PROPERTY_STATUS_TO_DB = [
        'متاح' => 'available',
        'مُؤجّر' => 'rented',
        'للإيجار' => 'for_rent',
        'للبيع' => 'for_sale',
        'available' => 'available',
        'rented' => 'rented',
        'for_rent' => 'for_rent',
        'for_sale' => 'for_sale',
        'sale' => 'sale',
    ];

    /** DB (payment_method) → Excel (Arabic) */
    public const PAYMENT_METHOD_TO_EXCEL = [
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semi_annual' => 'نصف سنوي',
        'annual' => 'سنوي',
    ];

    /** Excel (Arabic or English) → DB (payment_method) */
    public const PAYMENT_METHOD_TO_DB = [
        'شهري' => 'monthly',
        'ربع سنوي' => 'quarterly',
        'نصف سنوي' => 'semi_annual',
        'سنوي' => 'annual',
        'monthly' => 'monthly',
        'quarterly' => 'quarterly',
        'semi_annual' => 'semi_annual',
        'annual' => 'annual',
    ];

    public static function purposeToExcel(?string $db): string
    {
        if ($db === null || $db === '') {
            return '';
        }
        return self::PURPOSE_TO_EXCEL[$db] ?? $db;
    }

    public static function typeToExcel(?string $db): string
    {
        if ($db === null || $db === '') {
            return '';
        }
        return self::TYPE_TO_EXCEL[$db] ?? $db;
    }

    /**
     * Excel value (Arabic or English) → DB value for purpose.
     */
    public static function purposeToDb($value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        $v = is_string($value) ? trim($value) : (string) $value;
        if (isset(self::PURPOSE_TO_DB[$v])) {
            return self::PURPOSE_TO_DB[$v];
        }
        $lower = mb_strtolower($v);
        return in_array($lower, ['sale', 'rent']) ? $lower : $value;
    }

    /**
     * Excel value (Arabic or English) → DB value for type.
     */
    public static function typeToDb($value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        $v = is_string($value) ? trim($value) : (string) $value;
        if (isset(self::TYPE_TO_DB[$v])) {
            return self::TYPE_TO_DB[$v];
        }
        $lower = mb_strtolower($v);
        return in_array($lower, ['residential', 'commercial']) ? $lower : $value;
    }

    public static function purposeExcelOptions(): string
    {
        return 'بيع,إيجار';
    }

    public static function typeExcelOptions(): string
    {
        return 'سكني,تجاري';
    }

    /**
     * DB (property_status) → Excel (Arabic). For "حالة العقار" column.
     */
    public static function propertyStatusToExcel(?string $db): string
    {
        if ($db === null || $db === '') {
            return '';
        }
        return self::PROPERTY_STATUS_TO_EXCEL[$db] ?? $db;
    }

    /**
     * Excel value (Arabic or English) → DB (property_status). For import.
     */
    public static function propertyStatusToDb($value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        $v = is_string($value) ? trim($value) : (string) $value;
        if (isset(self::PROPERTY_STATUS_TO_DB[$v])) {
            return self::PROPERTY_STATUS_TO_DB[$v];
        }
        $lower = mb_strtolower($v);
        return in_array($lower, ['available', 'rented', 'for_rent', 'for_sale', 'sale']) ? $lower : null;
    }

    /**
     * DB (payment_method) → Excel (Arabic). For "طريقة الدفع" column.
     */
    public static function paymentMethodToExcel(?string $db): string
    {
        if ($db === null || $db === '') {
            return '';
        }
        return self::PAYMENT_METHOD_TO_EXCEL[$db] ?? $db;
    }

    /**
     * Excel value (Arabic or English) → DB (payment_method). For import.
     */
    public static function paymentMethodToDb($value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }
        $v = is_string($value) ? trim($value) : (string) $value;
        if (isset(self::PAYMENT_METHOD_TO_DB[$v])) {
            return self::PAYMENT_METHOD_TO_DB[$v];
        }
        $lower = mb_strtolower($v);
        return in_array($lower, ['monthly', 'quarterly', 'semi_annual', 'annual']) ? $lower : null;
    }

    public static function paymentMethodExcelOptions(): string
    {
        return 'شهري,ربع سنوي,نصف سنوي,سنوي';
    }

    public static function booleanToExcel(bool $v): string
    {
        return $v ? 'نعم' : 'لا';
    }

    /**
     * Excel value (نعم/Yes/1/true/y or لا/No/0/false) → bool.
     */
    public static function booleanToDb($value): bool
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return false;
        }
        $v = is_string($value) ? trim($value) : (string) $value;
        if ($v === 'نعم') {
            return true;
        }
        $lower = mb_strtolower($v);
        return in_array($lower, ['yes', '1', 'true', 'y']);
    }
}
