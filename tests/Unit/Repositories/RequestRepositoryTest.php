<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Api\UserPropertyRequest;
use App\Models\User;
use App\Repositories\RequestRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RequestRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private RequestRepository $repo;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new RequestRepository();
        $this->userId = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null])->id;
    }

    private function requireTable(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
    }

    private function insertPropertyRequest(array $data): int
    {
        $defaults = [
            'user_id'    => $this->userId,
            'full_name'  => 'Test',
            'phone'      => '+966501111111',
            'is_active'  => 1,
            'source'     => 'website',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        return (int) DB::table('users_property_requests')->insertGetId(array_merge($defaults, $data));
    }

    /** @test */
    public function web_branch_maps_standard_fields(): void
    {
        $this->requireTable();

        $id = $this->insertPropertyRequest([
            'purpose'     => 'rent',
            'property_type' => 'apartment',
            'budget_from' => 1000,
            'budget_to'   => 2000,
            'area_from'   => 80,
            'area_to'     => 150,
        ]);

        $unified = $this->repo->getUnified('web', $id);

        $this->assertNotNull($unified);
        $this->assertEquals('rent', $unified->purpose);
        $this->assertEquals('apartment', $unified->propertyType);
        $this->assertEquals(1000.0, $unified->budgetFrom);
        $this->assertEquals(2000.0, $unified->budgetTo);
        $this->assertEquals(80, $unified->areaFrom);
        $this->assertEquals(150, $unified->areaTo);
    }

    /** @test */
    public function web_branch_maps_whatsapp_origin_fields(): void
    {
        $this->requireTable();

        if (!Schema::hasColumn('users_property_requests', 'city')) {
            $this->markTestSkipped('city column not present (pre-March 2026 schema).');
        }

        $id = $this->insertPropertyRequest([
            'source'    => 'whatsapp',
            'city'      => 'جدة',
            'district'  => 'حي الزهراء',
            'bedrooms'  => 3,
            'bathrooms' => 2,
            'currency'  => 'SAR',
            'latitude'  => 21.4858,
            'longitude' => 39.1925,
        ]);

        $unified = $this->repo->getUnified('web', $id);

        $this->assertNotNull($unified);
        $this->assertEquals('جدة', $unified->cityName);
        $this->assertEquals('حي الزهراء', $unified->districtName);
        $this->assertEquals(3, $unified->bedrooms);
        $this->assertEquals(2, $unified->bathrooms);
        $this->assertEquals('SAR', $unified->currency);
        $this->assertEqualsWithDelta(21.4858, $unified->latitude, 0.0001);
        $this->assertEqualsWithDelta(39.1925, $unified->longitude, 0.0001);
    }

    /** @test */
    public function web_branch_returns_null_for_missing_id(): void
    {
        $this->requireTable();

        $unified = $this->repo->getUnified('web', 9999999);
        $this->assertNull($unified);
    }

    /** @test */
    public function unknown_source_returns_null(): void
    {
        $this->requireTable();

        $unified = $this->repo->getUnified('fax', 1);
        $this->assertNull($unified);
    }
}
