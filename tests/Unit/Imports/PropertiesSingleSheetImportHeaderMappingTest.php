<?php

declare(strict_types=1);

namespace Tests\Unit\Imports;

use App\Imports\PropertiesSingleSheetImport;
use Illuminate\Support\Str;
use ReflectionClass;
use Tests\TestCase;

class PropertiesSingleSheetImportHeaderMappingTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function headerKeyMap(): array
    {
        $method = (new ReflectionClass(PropertiesSingleSheetImport::class))->getMethod('headerKeyMap');
        $method->setAccessible(true);

        return $method->invoke(null);
    }

    public function test_header_key_map_includes_exact_arabic_and_maatwebsite_slugs(): void
    {
        $map = $this->headerKeyMap();

        $this->assertSame('title', $map['عنوان الإعلان']);
        $this->assertSame('title', $map[Str::slug('عنوان الإعلان', '_')]);
        $this->assertSame('aanoan_alaaalan', Str::slug('عنوان الإعلان', '_'));

        $this->assertSame('purpose', $map['الغرض']);
        $this->assertSame('purpose', $map[Str::slug('الغرض', '_')]);

        $this->assertSame('type', $map['النوع']);
        $this->assertSame('type', $map[Str::slug('النوع', '_')]);

        $this->assertSame('price', $map['السعر']);
        $this->assertSame('price', $map[Str::slug('السعر', '_')]);

        $this->assertSame('amenity_مصعد', $map['مصعد']);
        $this->assertSame('amenity_مصعد', $map[Str::slug('مصعد', '_')]);
        $this->assertSame('amenity_مصعد', $map[Str::slug('amenity_مصعد', '_')]);
    }

    public function test_normalize_row_keys_maps_slugified_arabic_headers(): void
    {
        $normalize = (new ReflectionClass(PropertiesSingleSheetImport::class))->getMethod('normalizeRowKeys');
        $normalize->setAccessible(true);

        // Bypass constructor language requirement by allocating without __construct
        $import = $this->newImportWithoutConstructor();

        $row = [
            Str::slug('عنوان الإعلان', '_') => 'شقة الرياض',
            Str::slug('السعر', '_') => 500000,
            Str::slug('الغرض', '_') => 'بيع',
            Str::slug('النوع', '_') => 'سكني',
            Str::slug('مصعد', '_') => 'نعم',
            'title' => 'should-not-overwrite-if-absent',
        ];
        unset($row['title']);

        $normalized = $normalize->invoke($import, $row);

        $this->assertSame('شقة الرياض', $normalized['title']);
        $this->assertSame(500000, $normalized['price']);
        $this->assertSame('بيع', $normalized['purpose']);
        $this->assertSame('سكني', $normalized['type']);
        $this->assertSame('نعم', $normalized['amenity_مصعد']);
        $this->assertArrayNotHasKey(Str::slug('عنوان الإعلان', '_'), $normalized);
    }

    public function test_prepare_for_validation_casts_numeric_unit_number_to_string(): void
    {
        $import = $this->newImportWithoutConstructor();

        $data = $import->prepareForValidation([
            Str::slug('رقم الوحدة', '_') => 301,
        ], 3);

        $this->assertSame('301', $data['unit_number']);
        $this->assertIsString($data['unit_number']);
    }

    public function test_prepare_for_validation_casts_numeric_boolean_like_fields_from_raw_export(): void
    {
        $import = $this->newImportWithoutConstructor();

        $data = $import->prepareForValidation([
            Str::slug('مصعد', '_') => 0,
            'featured' => 0,
            'balcony' => 0,
            'maid_room' => 0,
            'storage_room' => 0,
            'swimming_pool' => 0,
        ], 2);

        $this->assertSame('0', $data['amenity_مصعد']);
        $this->assertSame('0', $data['featured']);
        $this->assertSame('0', $data['balcony']);
        $this->assertSame('0', $data['maid_room']);
        $this->assertSame('0', $data['storage_room']);
        $this->assertSame('0', $data['swimming_pool']);
    }

    public function test_normalize_row_keys_keeps_first_non_empty_when_duplicate_headers_map_to_same_key(): void
    {
        $normalize = (new ReflectionClass(PropertiesSingleSheetImport::class))->getMethod('normalizeRowKeys');
        $normalize->setAccessible(true);
        $import = $this->newImportWithoutConstructor();

        $normalized = $normalize->invoke($import, [
            Str::slug('دورات المياه', '_') => 3,
            'bath' => null,
        ]);

        $this->assertSame(3, $normalized['bath']);
    }

    public function test_prepare_for_validation_remaps_headers_and_purpose_type(): void
    {
        $import = $this->newImportWithoutConstructor();

        $data = $import->prepareForValidation([
            Str::slug('عنوان الإعلان', '_') => 'وحدة تجريبية',
            Str::slug('الغرض', '_') => 'إيجار',
            Str::slug('النوع', '_') => 'تجاري',
            Str::slug('مصعد', '_') => 'نعم',
            Str::slug('أمن', '_') => 'لا',
        ], 2);

        $this->assertSame('وحدة تجريبية', $data['title']);
        $this->assertSame('rent', $data['purpose']);
        $this->assertSame('commercial', $data['type']);
        $this->assertSame('نعم', $data['amenity_مصعد']);
        $this->assertSame('لا', $data['amenity_أمن']);
    }

    public function test_normalize_row_keys_strips_trailing_required_asterisk(): void
    {
        $normalize = (new ReflectionClass(PropertiesSingleSheetImport::class))->getMethod('normalizeRowKeys');
        $normalize->setAccessible(true);
        $import = $this->newImportWithoutConstructor();

        $normalized = $normalize->invoke($import, [
            'عنوان الإعلان *' => 'شقة الرياض',
            'العنوان *' => 'حي العليا',
            'الوصف *' => 'وصف كافٍ للعقار المستورد',
            'النوع *' => 'سكني',
            'الصورة الرئيسية *' => 'https://example.com/img1.jpg',
        ]);

        $this->assertSame('شقة الرياض', $normalized['title']);
        $this->assertSame('حي العليا', $normalized['address']);
        $this->assertSame('وصف كافٍ للعقار المستورد', $normalized['description']);
        $this->assertSame('سكني', $normalized['type']);
        $this->assertSame('https://example.com/img1.jpg', $normalized['featured_image']);
    }

    public function test_template_shaped_row_is_not_marked_raw_export(): void
    {
        $import = $this->newImportWithoutConstructor();
        $method = (new ReflectionClass(PropertiesSingleSheetImport::class))->getMethod('isRawExportRow');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($import, [
            'title' => 'شقة',
            'address' => 'عنوان كافٍ',
            'description' => 'وصف كافٍ للاستيراد',
            'type' => 'residential',
            'featured_image' => 'https://example.com/a.jpg',
            // id alone is create-only ignore, not raw-export
            'id' => 42,
        ]));

        $this->assertFalse($method->invoke($import, [
            'title' => 'شقة',
            // Empty metadata headers must not reject (Option A)
            'user_id' => '',
            'created_at' => null,
        ]));
    }

    public function test_raw_export_row_with_metadata_values_is_detected(): void
    {
        $import = $this->newImportWithoutConstructor();
        $method = (new ReflectionClass(PropertiesSingleSheetImport::class))->getMethod('isRawExportRow');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($import, [
            'title' => 'شقة',
            'user_id' => 7,
            'created_at' => '2026-01-01 00:00:00',
        ]));
    }

    public function test_amenity_validation_rules_accept_arabic_yes_no(): void
    {
        $import = $this->newImportWithoutConstructor();
        $rules = $import->rules();

        foreach ([
            'amenity_مصعد',
            'amenity_أمن',
            'amenity_كاميرات_مراقبة',
            'amenity_تكييف_مركزي',
            'amenity_تدفئة_مركزية',
            'amenity_صيانة',
            'amenity_بواب',
            'amenity_إنترنت',
        ] as $field) {
            $this->assertArrayHasKey($field, $rules);
            $this->assertStringContainsString('نعم', $rules[$field]);
            $this->assertStringContainsString('لا', $rules[$field]);
        }
    }

    private function newImportWithoutConstructor(): PropertiesSingleSheetImport
    {
        return (new ReflectionClass(PropertiesSingleSheetImport::class))
            ->newInstanceWithoutConstructor();
    }
}
