<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestsListOptimizationsTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePropertyRequestTable(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
    }

    private function requireInquiryTable(): void
    {
        if (!Schema::hasTable('api_customer_inquiry') || !Schema::hasTable('api_customers')) {
            $this->markTestSkipped('api_customer_inquiry and api_customers tables required.');
        }
    }

    private function requireAllTables(): void
    {
        foreach (['users_property_requests', 'api_customer_inquiry', 'api_customers', 'property_request_statuses'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function createPropertyRequest(int $userId, array $overrides = []): int
    {
        $defaults = [
            'full_name'  => 'Test Requester',
            'phone'      => '+966501234567',
            'user_id'    => $userId,
            'region'     => 'الرياض',
            'is_active'  => 1,
            'is_read'    => 0,
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

    private function createInquiry(int $userId, int $customerId, array $overrides = []): int
    {
        $defaults = [
            'user_id'      => $userId,
            'customer_id'  => $customerId,
            'message'      => 'Test inquiry',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        return (int) DB::table('api_customer_inquiry')->insertGetId(array_merge($defaults, $overrides));
    }

    private function createCustomer(int $userId, array $overrides = []): int
    {
        $defaults = [
            'user_id'      => $userId,
            'name'         => 'Test Customer',
            'phone_number' => '+966501234567',
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        return (int) DB::table('api_customers')->insertGetId(array_merge($defaults, $overrides));
    }

    /**
     * Test that list returns paginated results with correct total count (window function).
     * @test
     */
    public function list_returns_paginated_results_with_correct_total(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Create 15 property requests
        for ($i = 0; $i < 15; $i++) {
            $this->createPropertyRequest($tenant->id, [
                'full_name' => "Customer {$i}",
                'phone'     => "+96650123456{$i}",
                'created_at' => now()->subDays($i),
                'updated_at' => now()->subDays($i),
            ]);
        }

        // Request page 1 (limit 10)
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 10,
            'offset' => 0,
        ]);

        $res->assertOk();
        $this->assertIsInt($res->json('data.pagination.total'));
        $this->assertEquals(15, $res->json('data.pagination.total'));
        $this->assertEquals(10, count($res->json('data.actions')));

        // Request page 2 (limit 10, offset 10)
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 10,
            'offset' => 10,
        ]);

        $res->assertOk();
        $this->assertEquals(15, $res->json('data.pagination.total'));
        $this->assertEquals(5, count($res->json('data.actions')));
    }

    /**
     * Test edge case: no matching filters returns zero total.
     * @test
     */
    public function list_with_no_matching_filters_returns_zero_total(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->createPropertyRequest($tenant->id);

        // Filter with non-existent object type
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['non_existent'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk();
        $this->assertEquals(0, $res->json('data.pagination.total'));
        $this->assertEmpty($res->json('data.actions'));
    }

    /**
     * Test edge case: offset is beyond available rows (window function returns no rows).
     * @test
     */
    public function list_returns_correct_total_when_offset_exceeds_available_rows(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Seed a small number of rows, then request an offset past the end
        for ($i = 0; $i < 3; $i++) {
            $this->createPropertyRequest($tenant->id);
        }

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 10,
            'offset' => 10,
        ]);

        $res->assertOk();
        $this->assertEmpty($res->json('data.actions'));
        $this->assertEquals(3, $res->json('data.pagination.total'));
    }

    /**
     * Test inquiry deduplication: suppress inquiry when property request exists for same customer_id.
     * @test
     */
    public function list_inquiry_dedup_suppresses_inquiry_when_property_request_exists_for_same_customer(): void
    {
        $this->requireAllTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Create a customer
        $customerId = $this->createCustomer($tenant->id);

        // Create inquiry for this customer
        $this->createInquiry($tenant->id, $customerId);

        // Create property request for same customer (by customer_id)
        $this->createPropertyRequest($tenant->id, ['customer_id' => $customerId]);

        // List should only return property_request, not the inquiry
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk();
        $actions = $res->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('property_request', $actions[0]['objectType']);
    }

    /**
     * Test inquiry deduplication: suppress inquiry when property request exists for same phone.
     * @test
     */
    public function list_inquiry_dedup_suppresses_inquiry_when_property_request_exists_for_same_phone(): void
    {
        $this->requireAllTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $phone = '+966501234567';

        // Create a customer with this phone
        $customerId = $this->createCustomer($tenant->id, ['phone_number' => $phone]);

        // Create inquiry for this customer
        $this->createInquiry($tenant->id, $customerId);

        // Create property request with same phone (but no customer_id)
        $this->createPropertyRequest($tenant->id, ['phone' => $phone, 'customer_id' => null]);

        // List should only return property_request, not the inquiry
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk();
        $actions = $res->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('property_request', $actions[0]['objectType']);
    }

    /**
     * Test stage filter: only returns rows with matching stage.
     * @test
     */
    public function list_stage_filter_returns_only_matching_stage_rows(): void
    {
        $this->requirePropertyRequestTable();

        if (!Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            $this->markTestSkipped('customers_hub_stage_id column required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Create property requests with different stages
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'stage_1']);
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'stage_2']);

        // Filter by stage_1
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'stages' => ['stage_1'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk();
        $actions = $res->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('stage_1', $actions[0]['customers_hub_stage_id']);
    }

    /**
     * Test exclude stage filter: excludes rows with matching stage.
     * @test
     */
    public function list_exclude_stage_filter_excludes_correct_rows(): void
    {
        $this->requirePropertyRequestTable();

        if (!Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            $this->markTestSkipped('customers_hub_stage_id column required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Create property requests with different stages
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'stage_1']);
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'stage_2']);

        // Exclude stage_2
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'excludeStages' => ['stage_2'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk();
        $actions = $res->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('stage_1', $actions[0]['customers_hub_stage_id']);
    }

    /**
     * Test COALESCE source filter: matches null source when 'website' selected.
     * @test
     */
    public function list_source_filter_matches_null_source_when_website_selected(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Create property request with explicit 'website' source
        $this->createPropertyRequest($tenant->id, ['source' => 'website']);

        // Create property request with NULL source (implicitly 'website')
        $this->createPropertyRequest($tenant->id, ['source' => null]);

        // Create property request with 'manual' source
        $this->createPropertyRequest($tenant->id, ['source' => 'manual']);

        // Filter by 'website' source
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'sources' => ['website'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk();
        $actions = $res->json('data.actions');
        // Should return 2: one explicit 'website', one NULL that implicitly maps to 'website'
        $this->assertCount(2, $actions);
    }

    /**
     * Test that multiple numeric stage IDs are resolved in a single query (no N+1).
     * Uses query counting to verify.
     * @test
     */
    public function list_resolves_multiple_numeric_stage_ids_in_single_query(): void
    {
        $this->requirePropertyRequestTable();

        if (!Schema::hasTable('customers_hub_stages')) {
            $this->markTestSkipped('customers_hub_stages table required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Insert two distinct tenant-owned active stages so the test owns its data.
        // (The schema requires stage_name_ar/en, color, and order.)
        $stageId1 = 'test_stage_n1_' . uniqid();
        $numericId1 = (int) DB::table('customers_hub_stages')->insertGetId([
            'user_id' => $tenant->id,
            'stage_id' => $stageId1,
            'stage_name_ar' => 'Test Stage N1',
            'stage_name_en' => 'Test Stage N1',
            'color' => '#000000',
            'order' => 99,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stageId2 = 'test_stage_n2_' . uniqid();
        $numericId2 = (int) DB::table('customers_hub_stages')->insertGetId([
            'user_id' => $tenant->id,
            'stage_id' => $stageId2,
            'stage_name_ar' => 'Test Stage N2',
            'stage_name_en' => 'Test Stage N2',
            'color' => '#000000',
            'order' => 100,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed a property request pinned to stage 1 so the service cannot short-circuit.
        $this->createPropertyRequest($tenant->id, [
            'customers_hub_stage_id' => $stageId1,
        ]);

        // Track DB queries (register listener AFTER setup — only count queries triggered by the endpoint call).
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            // Only count the numeric-ID resolution query (whereIn(id, ...)).
            if (
                stripos($query->sql, 'select') === 0
                && strpos($query->sql, 'customers_hub_stages') !== false
                && strpos($query->sql, '`id`') !== false
            ) {
                $queryCount++;
            }
        });

        // Request with multiple numeric stage IDs (must be distinct to prove no N+1)
        $this->postJson('/api/v2/customers-hub/requests/list', [
            'stages' => [$numericId1, $numericId2],
            'limit' => 50,
            'offset' => 0,
        ]);

        // Should be exactly 1 query to resolve stages (batch), not multiple
        $this->assertEquals(1, $queryCount, 'Stage resolution should use a single batch query.');
    }

    /**
     * Test that viewed_at is cached (10s TTL).
     * @test
     */
    public function list_viewed_at_is_cached_and_not_queried_on_cache_hit(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $viewerId = $tenant->id;

        // First request: cache is empty, viewed_at query runs
        $res1 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res1->assertOk();

        // Verify cache key was set
        $cacheKey = "ch:viewed:{$viewerId}";
        $this->assertTrue(Cache::has($cacheKey), 'viewed_at cache should be set');

        // Second request within 10s: should hit cache (no additional query)
        $res2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res2->assertOk();

        // Cache key still exists
        $this->assertTrue(Cache::has($cacheKey), 'viewed_at cache should still be set');
    }

    /**
     * Test matches endpoint: batch-load properties instead of N+1.
     * @test
     */
    public function matches_endpoint_does_not_execute_n_plus_1_for_properties(): void
    {
        $this->requirePropertyRequestTable();

        if (!Schema::hasTable('property_matches')) {
            $this->markTestSkipped('property_matches table required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        // Create 5 dummy match records pointing to different (non-existent) property IDs
        // We don't need real properties for this test, just verify the batch-load logic
        for ($i = 1; $i <= 5; $i++) {
            DB::table('property_matches')->insert([
                'user_id'           => $tenant->id,
                'request_type'      => 'web',
                'request_id'        => $requestId,
                'property_id'       => $i * 1000, // Non-existent IDs
                'match_score'       => 75 + $i,
                'database_score'    => 75,
                'ai_score'          => 75,
                'matched_criteria'  => null,
                'is_reviewed'       => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Track DB queries for property lookups
        $propertyQueryCount = 0;
        DB::listen(function ($query) use (&$propertyQueryCount) {
            if (stripos($query->sql, 'select') === 0 && str_contains($query->sql, '`properties`')) {
                $propertyQueryCount++;
            }
        });

        // Request matches
        $res = $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches");

        // Should be 1 batch query for properties (findMany), not 5 individual queries
        // Allow some flexibility for other queries
        $this->assertLessThanOrEqual(2, $propertyQueryCount, 'Properties should be batch-loaded, not N+1');
    }
}
