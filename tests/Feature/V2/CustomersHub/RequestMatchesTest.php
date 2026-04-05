<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestMatchesTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['users_property_requests', 'property_matches'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
        if (!Schema::hasColumn('users_property_requests', 'is_ignored')) {
            $this->markTestSkipped('is_ignored column required. Run migration.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function createPropertyRequest(int $userId, array $overrides = []): int
    {
        $defaults = [
            'user_id'    => $userId,
            'full_name'  => 'Test Customer',
            'phone'      => '+966501234567',
            'is_active'  => 1,
            'is_read'    => 0,
            'is_ignored' => 0,
            'source'     => 'website',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('users_property_requests', 'status_id')) {
            $statusId = DB::table('property_request_statuses')->where('is_active', true)->value('id');
            if ($statusId) {
                $defaults['status_id'] = $statusId;
            }
        }

        return (int) DB::table('users_property_requests')->insertGetId(array_merge($defaults, $overrides));
    }

    private function createMatch(int $userId, int $requestId, int $propertyId, int $score = 75): int
    {
        return (int) DB::table('property_matches')->insertGetId([
            'user_id'       => $userId,
            'customer_key'  => '9665012345',
            'request_type'  => 'web',
            'request_id'    => $requestId,
            'property_id'   => $propertyId,
            'match_score'   => $score,
            'database_score'=> 40,
            'ai_score'      => $score - 40,
            'is_reviewed'   => 0,
            'is_contacted'  => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /** @test */
    public function get_matches_returns_correct_structure(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        $requestCompositeId = "property_request_{$requestId}";

        $res = $this->getJson("/api/v2/customers-hub/requests/{$requestCompositeId}/matches");

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'request_id',
                    'source',
                    'has_minimal_data',
                    'minimal_missing_fields',
                    'is_complete',
                    'missing_fields',
                    'is_ignored',
                    'matches',
                    'total_matches',
                ],
            ]);
    }

    /** @test */
    public function get_matches_returns_property_matches(): void
    {
        $this->requireTables();
        if (!Schema::hasTable('properties')) {
            $this->markTestSkipped('properties table required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        // Insert a fake property match (property may not exist, match is still returned)
        $this->createMatch($tenant->id, $requestId, 9999, 80);

        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches");

        $res->assertOk();
        $this->assertEquals(1, $res->json('data.total_matches'));

        $match = $res->json('data.matches.0');
        $this->assertEquals(80, $match['match_score']);
        $this->assertFalse($match['is_reviewed']);
        $this->assertArrayHasKey('match_id', $match);
        $this->assertArrayHasKey('property_id', $match);
    }

    /** @test */
    public function get_matches_returns_minimal_data_missing_when_city_absent(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // No city, no property_type
        $requestId = $this->createPropertyRequest($tenant->id);
        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches");

        $res->assertOk();
        $this->assertFalse($res->json('data.has_minimal_data'));
        $this->assertNotEmpty($res->json('data.minimal_missing_fields'));
        $this->assertContains('location', $res->json('data.minimal_missing_fields'));
    }

    /** @test */
    public function get_matches_returns_empty_for_ignored_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['is_ignored' => 1]);
        $this->createMatch($tenant->id, $requestId, 9999, 70);

        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches");

        $res->assertOk();
        $this->assertTrue($res->json('data.is_ignored'));
    }

    /** @test */
    public function get_matches_returns_404_for_inquiry_composite_id(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // inquiry_* ids are not matchable in this flow
        $res = $this->getJson('/api/v2/customers-hub/requests/inquiry_1/matches');

        $res->assertStatus(404);
    }

    /** @test */
    public function get_matches_requires_authentication(): void
    {
        $res = $this->getJson('/api/v2/customers-hub/requests/property_request_1/matches');
        $res->assertUnauthorized();
    }

    /** @test */
    public function get_matches_scoped_to_tenant(): void
    {
        $this->requireTables();

        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        Sanctum::actingAs($tenant1);

        // Create a request belonging to tenant2
        $requestId = $this->createPropertyRequest($tenant2->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches");

        // Should not find tenant2's request when acting as tenant1
        $res->assertStatus(404);
    }

    /** @test */
    public function get_matches_shows_has_minimal_data_true_when_city_and_type_present(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches");

        $res->assertOk();
        $this->assertTrue($res->json('data.has_minimal_data'));
        $this->assertEmpty($res->json('data.minimal_missing_fields'));
    }
}
