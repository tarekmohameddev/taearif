<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Property\PropertyStatusSyncService;
use PHPUnit\Framework\TestCase;

class PropertyStatusSyncServiceTest extends TestCase
{
    /**
     * @dataProvider legacyPurposeProvider
     */
    public function test_sync_array_normalizes_accepted_purpose_input_with_lifecycle_fields(
        string $inputPurpose,
        string $expectedPurpose,
        string $expectedListingPurpose,
        string $expectedUnitStatus,
        string $expectedPropertyStatus
    ): void {
        $data = [
            'purpose' => $inputPurpose,
            'publish_status' => 'published',
            'status' => 1,
            'completion_status' => 'complete',
        ];

        (new PropertyStatusSyncService())->syncArray($data, false);

        $this->assertSame($expectedPurpose, $data['purpose']);
        $this->assertSame($expectedListingPurpose, $data['listing_purpose']);
        $this->assertSame($expectedUnitStatus, $data['unit_status']);
        $this->assertSame($expectedPropertyStatus, $data['property_status']);
        $this->assertSame('published', $data['publish_status']);
        $this->assertSame(1, $data['status']);
    }

    public function test_explicit_consistent_modern_fields_take_precedence_over_legacy_purpose(): void
    {
        $data = [
            'purpose' => 'sold',
            'listing_purpose' => 'rent',
            'unit_status' => 'rented',
            'publish_status' => 'published',
        ];

        (new PropertyStatusSyncService())->syncArray($data, false);

        $this->assertSame('rent', $data['purpose']);
        $this->assertSame('rent', $data['listing_purpose']);
        $this->assertSame('rented', $data['unit_status']);
        $this->assertSame('rented', $data['property_status']);
        $this->assertSame(1, $data['status']);
    }

    /**
     * @return array<string, array{string, string, string, string, string}>
     */
    public static function legacyPurposeProvider(): array
    {
        return [
            'sale' => ['sale', 'sale', 'sale', 'available', 'available'],
            'rent' => ['rent', 'rent', 'rent', 'available', 'available'],
            'sold' => ['sold', 'sale', 'sale', 'sold', 'sale'],
            'rented' => ['rented', 'rent', 'rent', 'rented', 'rented'],
        ];
    }
}
