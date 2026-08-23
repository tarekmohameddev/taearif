<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;
use Tests\TestCase;

final class PropertySearchCategoryMapTest extends TestCase
{
    public function test_apartment_maps_to_category_ids(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('apartment');
        $this->assertContains(3, $ids);   // شقة في عمارة
        $this->assertContains(18, $ids);  // شقة
        $this->assertEquals('residential', $broad);
    }

    public function test_arabic_apartment_maps_correctly(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('شقة');
        $this->assertContains(3, $ids);
        $this->assertContains(18, $ids);
        $this->assertEquals('residential', $broad);
    }

    public function test_villa_maps_to_category_id_1(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('villa');
        $this->assertContains(1, $ids);
        $this->assertEquals('residential', $broad);
    }

    public function test_townhouse_maps_to_villa_category_as_fallback(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('townhouse');
        $this->assertContains(1, $ids);
        $this->assertEquals('residential', $broad);
    }

    public function test_arabic_townhouse_maps_to_villa_category_as_fallback(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('تاون هاوس');
        $this->assertContains(1, $ids);
        $this->assertEquals('residential', $broad);
    }

    public function test_arabic_villa_falla_spelling_maps_correctly(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('فله');
        $this->assertContains(1, $ids);
        $this->assertEquals('residential', $broad);
    }

    public function test_building_maps_to_category_id_15(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('building');
        $this->assertContains(15, $ids);
    }

    public function test_arabic_building_maps_correctly(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('عمارة');
        $this->assertContains(15, $ids);
    }

    public function test_land_maps_to_category_id_4(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('land');
        $this->assertContains(4, $ids);
        $this->assertNull($broad);
    }

    public function test_office_maps_to_category_id_9(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('office');
        $this->assertContains(9, $ids);
        $this->assertEquals('commercial', $broad);
    }

    public function test_warehouse_maps_to_category_id_12(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('warehouse');
        $this->assertContains(12, $ids);
        $this->assertEquals('commercial', $broad);
    }

    public function test_duplex_maps_to_category_id_13(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('duplex');
        $this->assertContains(13, $ids);
    }

    public function test_unknown_type_returns_empty_array(): void
    {
        [$ids, $broad] = PropertySearchTool::resolveTypeToCategories('unknown_xyz');
        $this->assertEmpty($ids);
        $this->assertNull($broad);
    }

    public function test_type_matching_is_case_insensitive(): void
    {
        [$ids] = PropertySearchTool::resolveTypeToCategories('VILLA');
        // English uppercase is lowercased before lookup
        $this->assertContains(1, $ids);
    }
}
