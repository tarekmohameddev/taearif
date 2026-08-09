<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Maps Excel column headers (Arabic template + English snake_case) to internal row keys.
 * Subset of PropertiesSingleSheetImport::arabicHeaderToKey() for bulk import v1.
 */
class PropertyExcelHeaderMapping
{
    /**
     * @return array<string, string>
     */
    public static function arabicHeaderToKey(): array
    {
        return [
            'عنوان الإعلان' => 'title',
            'السعر' => 'price',
            'العنوان' => 'address',
            'الوصف' => 'description',
            'الغرض' => 'purpose',
            'النوع' => 'type',
            'المساحة' => 'area',
            'غرف النوم' => 'beds',
            'دورات المياه' => 'bath',
        ];
    }

    public static function headerToKey(string $header): string
    {
        $header = trim($header);
        if ($header === '') {
            return '';
        }

        // Strip trailing asterisk (and surrounding whitespace) used to mark required columns
        $header = trim(preg_replace('/\s*\*\s*$/', '', $header));
        if ($header === '') {
            return '';
        }

        $map = self::arabicHeaderToKey();
        if (isset($map[$header])) {
            return $map[$header];
        }

        return Str::snake($header);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeRowData(array $data): array
    {
        if (isset($data['purpose']) && ! isset($data['listing_purpose'])) {
            $data['listing_purpose'] = PropertyExcelMapping::purposeToDb($data['purpose']);
        }

        if (isset($data['listing_purpose']) && $data['listing_purpose'] !== null && $data['listing_purpose'] !== '') {
            $data['listing_purpose'] = PropertyExcelMapping::purposeToDb($data['listing_purpose']);
        }

        if (isset($data['type']) && ! isset($data['property_type'])) {
            $data['property_type'] = PropertyExcelMapping::typeToDb($data['type']);
            unset($data['type']);
        } elseif (isset($data['property_type']) && $data['property_type'] !== null && $data['property_type'] !== '') {
            $mapped = PropertyExcelMapping::typeToDb($data['property_type']);
            if ($mapped !== null) {
                $data['property_type'] = $mapped;
            }
        }

        unset($data['purpose']);

        return $data;
    }
}
