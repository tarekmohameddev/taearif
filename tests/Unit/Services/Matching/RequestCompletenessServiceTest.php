<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Matching;

use App\Repositories\RequestRepository;
use App\Services\Matching\RequestCompletenessService;
use App\Support\DTO\UnifiedRequest;
use Tests\TestCase;

class RequestCompletenessServiceTest extends TestCase
{
    private RequestCompletenessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $repo = $this->createMock(RequestRepository::class);
        $this->service = new RequestCompletenessService($repo);
    }

    private function makeRequest(array $props = []): UnifiedRequest
    {
        $req = new UnifiedRequest('web', 1);
        foreach ($props as $k => $v) {
            $req->$k = $v;
        }
        return $req;
    }

    // =========================================================================
    // hasMinimalData / getMinimalMissingFields
    // =========================================================================

    /** @test */
    public function has_minimal_data_true_when_city_name_and_property_type_present(): void
    {
        $req = $this->makeRequest(['cityName' => 'الرياض', 'propertyType' => 'apartment']);
        $this->assertTrue($this->service->hasMinimalData($req));
        $this->assertEmpty($this->service->getMinimalMissingFields($req));
    }

    /** @test */
    public function has_minimal_data_true_when_city_id_and_category_id_present(): void
    {
        $req = $this->makeRequest(['cityId' => 5, 'categoryId' => 2]);
        $this->assertTrue($this->service->hasMinimalData($req));
    }

    /** @test */
    public function missing_location_returns_location_in_minimal_missing(): void
    {
        $req = $this->makeRequest(['propertyType' => 'apartment']);
        $missing = $this->service->getMinimalMissingFields($req);
        $this->assertContains('location', $missing);
        $this->assertFalse($this->service->hasMinimalData($req));
    }

    /** @test */
    public function missing_category_returns_category_in_minimal_missing(): void
    {
        $req = $this->makeRequest(['cityName' => 'الرياض']);
        $missing = $this->service->getMinimalMissingFields($req);
        $this->assertContains('category', $missing);
        $this->assertFalse($this->service->hasMinimalData($req));
    }

    /** @test */
    public function missing_both_returns_both_in_minimal_missing(): void
    {
        $req = $this->makeRequest([]);
        $missing = $this->service->getMinimalMissingFields($req);
        $this->assertContains('location', $missing);
        $this->assertContains('category', $missing);
        $this->assertCount(2, $missing);
    }

    /** @test */
    public function district_name_satisfies_location(): void
    {
        $req = $this->makeRequest(['districtName' => 'حي النزهة', 'propertyType' => 'villa']);
        $this->assertTrue($this->service->hasMinimalData($req));
    }

    /** @test */
    public function lat_lng_satisfies_location(): void
    {
        $req = $this->makeRequest(['latitude' => 24.7, 'longitude' => 46.6, 'propertyType' => 'apartment']);
        $this->assertTrue($this->service->hasMinimalData($req));
    }

    /** @test */
    public function region_satisfies_location(): void
    {
        $req = $this->makeRequest(['region' => 'الرياض', 'propertyType' => 'land']);
        $this->assertTrue($this->service->hasMinimalData($req));
    }

    // =========================================================================
    // validateMinimal
    // =========================================================================

    /** @test */
    public function validate_minimal_returns_not_found_for_missing_id(): void
    {
        $repo = $this->createMock(RequestRepository::class);
        $repo->method('getUnified')->willReturn(null);
        $service = new RequestCompletenessService($repo);

        $result = $service->validateMinimal('web', 99999);

        $this->assertFalse($result['has_minimal_data']);
        $this->assertContains('not_found', $result['minimal_missing_fields']);
        $this->assertNull($result['unified']);
    }

    /** @test */
    public function validate_minimal_returns_complete_info_for_full_request(): void
    {
        $unified = $this->makeRequest([
            'cityId'      => 1,
            'categoryId'  => 1,
            'purpose'     => 'rent',
            'budgetFrom'  => 1000,
            'areaFrom'    => 100,
        ]);
        $unified->userId = 1;

        $repo = $this->createMock(RequestRepository::class);
        $repo->method('getUnified')->willReturn($unified);
        $service = new RequestCompletenessService($repo);

        $result = $service->validateMinimal('web', 1);

        $this->assertTrue($result['has_minimal_data']);
        $this->assertEmpty($result['minimal_missing_fields']);
        $this->assertTrue($result['is_complete']);
        $this->assertEmpty($result['missing_fields']);
        $this->assertNotNull($result['unified']);
    }

    // =========================================================================
    // Full completeness (existing behavior still correct)
    // =========================================================================

    /** @test */
    public function get_missing_fields_returns_all_missing_for_empty_request(): void
    {
        $req = $this->makeRequest([]);
        $missing = $this->service->getMissingFields($req);
        $this->assertContains('purpose', $missing);
        $this->assertContains('budget', $missing);
        $this->assertContains('area', $missing);
        $this->assertContains('category', $missing);
        $this->assertContains('location', $missing);
    }

    /** @test */
    public function infer_purpose_handles_arabic_rent(): void
    {
        $req = $this->makeRequest(['purpose' => 'ايجار']);
        $this->assertEquals('rent', $this->service->inferPurpose($req));
    }

    /** @test */
    public function infer_purpose_handles_english_buy(): void
    {
        $req = $this->makeRequest(['purpose' => 'buy']);
        $this->assertEquals('sale', $this->service->inferPurpose($req));
    }
}
