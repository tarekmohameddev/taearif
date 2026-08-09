<?php

namespace Tests\Feature;

use App\Support\PropertyExcelHeaderMapping;
use Tests\TestCase;

class PropertyExcelHeaderAsteriskTest extends TestCase
{
    /**
     * Test that Arabic headers with trailing asterisk map correctly.
     */
    public function test_arabic_header_with_asterisk_space()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('عنوان الإعلان *');
        $this->assertEquals('title', $result);
    }

    /**
     * Test that Arabic headers without asterisk still work (no regression).
     */
    public function test_arabic_header_without_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('عنوان الإعلان');
        $this->assertEquals('title', $result);
    }

    /**
     * Test Arabic address header with asterisk.
     */
    public function test_arabic_address_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('العنوان *');
        $this->assertEquals('address', $result);
    }

    /**
     * Test Arabic address header without asterisk.
     */
    public function test_arabic_address_header_without_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('العنوان');
        $this->assertEquals('address', $result);
    }

    /**
     * Test Arabic description header with asterisk.
     */
    public function test_arabic_description_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('الوصف *');
        $this->assertEquals('description', $result);
    }

    /**
     * Test Arabic description header without asterisk.
     */
    public function test_arabic_description_header_without_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('الوصف');
        $this->assertEquals('description', $result);
    }

    /**
     * Test Arabic type header with asterisk.
     */
    public function test_arabic_type_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('النوع *');
        $this->assertEquals('type', $result);
    }

    /**
     * Test Arabic type header without asterisk.
     */
    public function test_arabic_type_header_without_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('النوع');
        $this->assertEquals('type', $result);
    }

    /**
     * Test Arabic price header with asterisk.
     */
    public function test_arabic_price_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('السعر *');
        $this->assertEquals('price', $result);
    }

    /**
     * Test Arabic purpose header with asterisk.
     */
    public function test_arabic_purpose_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('الغرض *');
        $this->assertEquals('purpose', $result);
    }

    /**
     * Test Arabic area header with asterisk.
     */
    public function test_arabic_area_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('المساحة *');
        $this->assertEquals('area', $result);
    }

    /**
     * Test Arabic beds header with asterisk.
     */
    public function test_arabic_beds_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('غرف النوم *');
        $this->assertEquals('beds', $result);
    }

    /**
     * Test Arabic bath header with asterisk.
     */
    public function test_arabic_bath_header_with_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('دورات المياه *');
        $this->assertEquals('bath', $result);
    }

    /**
     * Test Arabic header with asterisk but no space.
     */
    public function test_arabic_header_with_asterisk_no_space()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('عنوان الإعلان*');
        $this->assertEquals('title', $result);
    }

    /**
     * Test Arabic header with asterisk and extra whitespace.
     */
    public function test_arabic_header_with_asterisk_extra_whitespace()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('عنوان الإعلان  *  ');
        $this->assertEquals('title', $result);
    }

    /**
     * Test Arabic header with multiple spaces before asterisk.
     */
    public function test_arabic_header_with_asterisk_multiple_spaces_before()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('العنوان   *');
        $this->assertEquals('address', $result);
    }

    /**
     * Test Arabic header with spaces on both sides of asterisk.
     */
    public function test_arabic_header_with_asterisk_spaces_both_sides()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('الوصف  *  ');
        $this->assertEquals('description', $result);
    }

    /**
     * Test English header with asterisk space.
     */
    public function test_english_header_with_asterisk_space()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('Title *');
        // English headers fall through to Str::snake()
        $this->assertEquals('title', $result);
    }

    /**
     * Test English header without asterisk.
     */
    public function test_english_header_without_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('Title');
        $this->assertEquals('title', $result);
    }

    /**
     * Test header that is only an asterisk.
     */
    public function test_header_only_asterisk()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('*');
        $this->assertEquals('', $result);
    }

    /**
     * Test header that is only asterisk with spaces.
     */
    public function test_header_only_asterisk_with_spaces()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('  *  ');
        $this->assertEquals('', $result);
    }

    /**
     * Test empty header (no regression).
     */
    public function test_empty_header()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('');
        $this->assertEquals('', $result);
    }

    /**
     * Test header with only spaces (no regression).
     */
    public function test_header_only_spaces()
    {
        $result = PropertyExcelHeaderMapping::headerToKey('   ');
        $this->assertEquals('', $result);
    }

    /**
     * Test that asterisks in the middle of the header are NOT stripped.
     * (Only trailing asterisks should be stripped)
     */
    public function test_asterisk_in_middle_not_stripped()
    {
        // A header with asterisk in the middle should snake_case it
        // since it won't match the Arabic map exactly
        $result = PropertyExcelHeaderMapping::headerToKey('Title * Description');
        $this->assertEquals('title*_description', $result);
    }

    /**
     * Test Arabic header with trailing asterisk but internal asterisk preserved.
     * (Only the trailing asterisk is stripped)
     */
    public function test_arabic_with_internal_text_and_trailing_asterisk()
    {
        // This should strip only the trailing asterisk
        // The internal structure is preserved before stripping
        $result = PropertyExcelHeaderMapping::headerToKey('عنوان الإعلان *');
        $this->assertEquals('title', $result);
    }
}
