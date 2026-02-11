<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestsListStagesTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePropertyRequestTables(): void
    {
        if (!Schema::hasTable('users_property_requests') || !Schema::hasTable('property_request_statuses')) {
            $this->markTestSkipped('users_property_requests and property_request_statuses tables required.');
        }
    }

    private function getStatusIds(): array
    {
        $rows = DB::table('property_request_statuses')
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'slug']);
        $bySlug = [];
        foreach ($rows as $r) {
            $bySlug[$r->slug] = (int) $r->id;
        }
        return $bySlug;
    }

    /**
     * Insert a property request; optionally set status_id and is_archived when columns exist.
     */
    private function createPropertyRequest(int $userId, ?int $statusId = null, bool $isArchived = false): int
    {
        $data = [
            'full_name' => 'Test Requester',
            'phone' => '+966501234567',
            'user_id' => $userId,
            'region' => 'الرياض',
            'is_active' => 1,
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users_property_requests', 'status_id')) {
            $data['status_id'] = $statusId;
        }
        if (Schema::hasColumn('users_property_requests', 'is_archived')) {
            $data['is_archived'] = $isArchived ? 1 : 0;
        }
        $id = DB::table('users_property_requests')->insertGetId($data);
        return (int) $id;
    }

    /** @test */
    public function stages_have_request_count_and_percentage_when_object_types_property_request_only(): void
    {
        $this->requirePropertyRequestTables();
        $statusIds = $this->getStatusIds();
        if (empty($statusIds)) {
            $this->markTestSkipped('property_request_statuses has no active statuses.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $firstStatusId = reset($statusIds);
        $this->createPropertyRequest($tenant->id, $firstStatusId);
        $this->createPropertyRequest($tenant->id, $firstStatusId);
        $this->createPropertyRequest($tenant->id, $firstStatusId);

        $secondStatusId = next($statusIds) ?: $firstStatusId;
        $this->createPropertyRequest($tenant->id, $secondStatusId);
        $this->createPropertyRequest($tenant->id, $secondStatusId);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $stages = $res->json('data.stages');
        $this->assertIsArray($stages);

        $totalCount = 0;
        $totalPercentage = 0.0;
        foreach ($stages as $stage) {
            $this->assertArrayHasKey('stage_id', $stage);
            $this->assertArrayHasKey('requestCount', $stage);
            $this->assertArrayHasKey('percentage', $stage);
            $totalCount += (int) $stage['requestCount'];
            $totalPercentage += (float) $stage['percentage'];
        }
        $this->assertSame(5, $totalCount, 'Total requestCount across stages should equal number of created requests');
        $this->assertGreaterThanOrEqual(99.0, $totalPercentage);
        $this->assertLessThanOrEqual(101.0, $totalPercentage);
    }

    /** @test */
    public function stages_tab_completed_only_counts_archived_requests(): void
    {
        $this->requirePropertyRequestTables();
        if (!Schema::hasColumn('users_property_requests', 'is_archived')) {
            $this->markTestSkipped('users_property_requests.is_archived column required.');
        }
        $statusIds = $this->getStatusIds();
        if (empty($statusIds)) {
            $this->markTestSkipped('property_request_statuses has no active statuses.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $statusId = reset($statusIds);
        $this->createPropertyRequest($tenant->id, $statusId, false);
        $this->createPropertyRequest($tenant->id, $statusId, false);
        $this->createPropertyRequest($tenant->id, $statusId, true);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'completed',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $stages = $res->json('data.stages');
        $this->assertIsArray($stages);
        $totalCount = 0;
        foreach ($stages as $stage) {
            $totalCount += (int) $stage['requestCount'];
        }
        $this->assertSame(1, $totalCount, 'Tab completed should count only archived (1) request');
    }

    /** @test */
    public function stages_empty_result_returns_all_zero(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $stages = $res->json('data.stages');
        $this->assertIsArray($stages);
        foreach ($stages as $stage) {
            $this->assertSame(0, $stage['requestCount']);
            $this->assertEquals(0, $stage['percentage'], 'Percentage should be 0 when no requests');
        }
    }

    /** @test */
    public function stages_pagination_counts_full_set_not_page(): void
    {
        $this->requirePropertyRequestTables();
        $statusIds = $this->getStatusIds();
        if (empty($statusIds)) {
            $this->markTestSkipped('property_request_statuses has no active statuses.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $statusId = reset($statusIds);
        for ($i = 0; $i < 5; $i++) {
            $this->createPropertyRequest($tenant->id, $statusId);
        }

        $resFirst = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 2,
            'offset' => 0,
        ]);
        $resSecond = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 2,
            'offset' => 2,
        ]);

        $resFirst->assertOk();
        $resSecond->assertOk();

        $stagesFirst = $resFirst->json('data.stages');
        $stagesSecond = $resSecond->json('data.stages');
        $this->assertIsArray($stagesFirst);
        $this->assertIsArray($stagesSecond);

        $totalCountFirst = 0;
        $totalCountSecond = 0;
        foreach ($stagesFirst as $s) {
            $totalCountFirst += (int) $s['requestCount'];
        }
        foreach ($stagesSecond as $s) {
            $totalCountSecond += (int) $s['requestCount'];
        }
        $this->assertSame(5, $totalCountFirst, 'Stage counts should reflect full filtered set (5), not page size (2)');
        $this->assertSame(5, $totalCountSecond, 'Stage counts should be same regardless of offset');
    }

    /** @test */
    public function stages_mixed_object_types_returns_customers_hub_stages_structure(): void
    {
        $this->requirePropertyRequestTables();
        if (!Schema::hasTable('customers_hub_stages')) {
            $this->markTestSkipped('customers_hub_stages table required for mixed objectTypes.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request', 'inquiry'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $stages = $res->json('data.stages');
        $this->assertIsArray($stages);
        foreach ($stages as $stage) {
            $this->assertArrayHasKey('stage_id', $stage);
            $this->assertArrayHasKey('stage_name_ar', $stage);
            $this->assertArrayHasKey('stage_name_en', $stage);
            $this->assertArrayHasKey('requestCount', $stage);
            $this->assertArrayHasKey('percentage', $stage);
        }
    }

    /** @test */
    public function stages_property_request_only_returns_property_request_status_slugs(): void
    {
        $this->requirePropertyRequestTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $stages = $res->json('data.stages');
        $this->assertIsArray($stages);
        $expectedSlugs = ['new', 'follow_up', 'property_found', 'contract_signed', 'cancelled'];
        $actualSlugs = array_column($stages, 'stage_id');
        foreach ($expectedSlugs as $slug) {
            $this->assertContains($slug, $actualSlugs, "Stages should include property_request_status slug: {$slug}");
        }
    }
}
