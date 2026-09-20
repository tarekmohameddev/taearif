<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Domain\CustomersHub\Services\CustomersHubNotificationService;
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
        $expectedSlugs = ['suspended', 'in_progress', 'waiting', 'completed', 'cancelled'];
        $actualSlugs = array_column($stages, 'stage_id');
        foreach ($expectedSlugs as $slug) {
            $this->assertContains($slug, $actualSlugs, "Stages should include property_request_status slug: {$slug}");
        }
    }

    /** @test */
    public function list_default_sort_is_updated_at_desc(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        // Create three property requests, then tweak updated_at to known values
        $id1 = $this->createPropertyRequest($tenant->id);
        $id2 = $this->createPropertyRequest($tenant->id);
        $id3 = $this->createPropertyRequest($tenant->id);

        $now = now();
        DB::table('users_property_requests')->where('id', $id1)->update(['updated_at' => $now->copy()->subMinutes(10)]);
        DB::table('users_property_requests')->where('id', $id2)->update(['updated_at' => $now->copy()->subMinutes(5)]);
        DB::table('users_property_requests')->where('id', $id3)->update(['updated_at' => $now]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 10,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $pagination = $res->json('data.pagination');
        $this->assertSame('updatedAt', $pagination['sortBy'] ?? null);
        $this->assertSame('desc', $pagination['sortDir'] ?? null);

        $actions = $res->json('data.actions');
        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);

        // The newest updated_at (id3) should come first, then id2, then id1
        $firstThree = array_slice($actions, 0, 3);
        $sourceIds = array_column($firstThree, 'sourceId');
        $this->assertSame([$id3, $id2, $id1], $sourceIds, 'Default sort should be updatedAt desc for property_request actions');
    }

    /**
     * Stub unread property-request source IDs returned by the notification service
     * (RequestsController always injects them into getList filters).
     *
     * @param  list<int>  $sourceIds
     */
    private function mockUnreadPropertyRequestSourceIds(array $sourceIds): void
    {
        $this->partialMock(CustomersHubNotificationService::class, function ($mock) use ($sourceIds) {
            $mock->shouldReceive('getUnreadPropertyRequestSourceIds')
                ->andReturn($sourceIds);
        });
    }

    /** @test */
    public function explicit_updated_at_desc_with_unread_ids_does_not_float_unread_first(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $unreadOlder = $this->createPropertyRequest($tenant->id);
        $readNewer = $this->createPropertyRequest($tenant->id);

        $now = now();
        DB::table('users_property_requests')->where('id', $unreadOlder)->update([
            'updated_at' => $now->copy()->subMinutes(30),
            'created_at' => $now->copy()->subMinutes(30),
        ]);
        DB::table('users_property_requests')->where('id', $readNewer)->update([
            'updated_at' => $now,
            'created_at' => $now->copy()->subMinutes(1),
        ]);

        $this->mockUnreadPropertyRequestSourceIds([$unreadOlder]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'sort_by' => 'updatedAt',
            'sort_dir' => 'desc',
            'limit' => 10,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('updatedAt', $res->json('data.pagination.sortBy'));
        $this->assertSame('desc', $res->json('data.pagination.sortDir'));

        $actions = $res->json('data.actions');
        $sourceIds = array_column(array_slice($actions, 0, 2), 'sourceId');
        $this->assertSame(
            [$readNewer, $unreadOlder],
            $sourceIds,
            'Explicit updatedAt sort must ignore unread-first even when unread IDs are present'
        );
    }

    /** @test */
    public function explicit_updated_at_desc_with_no_unread_ids_orders_by_updated_at(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $id1 = $this->createPropertyRequest($tenant->id);
        $id2 = $this->createPropertyRequest($tenant->id);
        $id3 = $this->createPropertyRequest($tenant->id);

        $now = now();
        DB::table('users_property_requests')->where('id', $id1)->update(['updated_at' => $now->copy()->subMinutes(10)]);
        DB::table('users_property_requests')->where('id', $id2)->update(['updated_at' => $now->copy()->subMinutes(5)]);
        DB::table('users_property_requests')->where('id', $id3)->update(['updated_at' => $now]);

        $this->mockUnreadPropertyRequestSourceIds([]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'sort_by' => 'updatedAt',
            'sort_dir' => 'desc',
            'limit' => 10,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('updatedAt', $res->json('data.pagination.sortBy'));
        $this->assertSame('desc', $res->json('data.pagination.sortDir'));

        $sourceIds = array_column(array_slice($res->json('data.actions'), 0, 3), 'sourceId');
        $this->assertSame([$id3, $id2, $id1], $sourceIds);
    }

    /** @test */
    public function explicit_created_at_asc_and_desc_report_metadata_and_order(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $oldest = $this->createPropertyRequest($tenant->id);
        $middle = $this->createPropertyRequest($tenant->id);
        $newest = $this->createPropertyRequest($tenant->id);

        $now = now();
        DB::table('users_property_requests')->where('id', $oldest)->update(['created_at' => $now->copy()->subMinutes(30)]);
        DB::table('users_property_requests')->where('id', $middle)->update(['created_at' => $now->copy()->subMinutes(15)]);
        DB::table('users_property_requests')->where('id', $newest)->update(['created_at' => $now]);

        $this->mockUnreadPropertyRequestSourceIds([]);

        $resDesc = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'sort_by' => 'createdAt',
            'sort_dir' => 'desc',
            'limit' => 10,
            'offset' => 0,
        ]);

        $resDesc->assertOk();
        $this->assertSame('createdAt', $resDesc->json('data.pagination.sortBy'));
        $this->assertSame('desc', $resDesc->json('data.pagination.sortDir'));
        $this->assertSame(
            [$newest, $middle, $oldest],
            array_column(array_slice($resDesc->json('data.actions'), 0, 3), 'sourceId')
        );

        $resAsc = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'sort_by' => 'createdAt',
            'sort_dir' => 'asc',
            'limit' => 10,
            'offset' => 0,
        ]);

        $resAsc->assertOk();
        $this->assertSame('createdAt', $resAsc->json('data.pagination.sortBy'));
        $this->assertSame('asc', $resAsc->json('data.pagination.sortDir'));
        $this->assertSame(
            [$oldest, $middle, $newest],
            array_column(array_slice($resAsc->json('data.actions'), 0, 3), 'sourceId')
        );
    }

    /** @test */
    public function default_sort_with_unread_ids_is_unread_first_then_created_at_desc(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $unreadOlder = $this->createPropertyRequest($tenant->id);
        $readNewest = $this->createPropertyRequest($tenant->id);
        $readMiddle = $this->createPropertyRequest($tenant->id);

        $now = now();
        // Unread item is oldest by createdAt — without unread-first it would be last.
        DB::table('users_property_requests')->where('id', $unreadOlder)->update([
            'created_at' => $now->copy()->subMinutes(60),
            'updated_at' => $now->copy()->subMinutes(60),
        ]);
        DB::table('users_property_requests')->where('id', $readNewest)->update([
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('users_property_requests')->where('id', $readMiddle)->update([
            'created_at' => $now->copy()->subMinutes(10),
            'updated_at' => $now->copy()->subMinutes(10),
        ]);

        $this->mockUnreadPropertyRequestSourceIds([$unreadOlder]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 10,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $this->assertSame('createdAt', $res->json('data.pagination.sortBy'));
        $this->assertSame('desc', $res->json('data.pagination.sortDir'));

        $sourceIds = array_column(array_slice($res->json('data.actions'), 0, 3), 'sourceId');
        $this->assertSame(
            [$unreadOlder, $readNewest, $readMiddle],
            $sourceIds,
            'When sort_by omitted and unread IDs present, unread floats first then createdAt desc'
        );
    }

    /** @test */
    public function identical_updated_at_uses_source_id_desc_as_tiebreaker(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $id1 = $this->createPropertyRequest($tenant->id);
        $id2 = $this->createPropertyRequest($tenant->id);
        $id3 = $this->createPropertyRequest($tenant->id);

        $sameUpdatedAt = now()->subMinutes(5)->toDateTimeString();
        DB::table('users_property_requests')->whereIn('id', [$id1, $id2, $id3])->update([
            'updated_at' => $sameUpdatedAt,
        ]);

        $this->mockUnreadPropertyRequestSourceIds([]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'sort_by' => 'updatedAt',
            'sort_dir' => 'desc',
            'limit' => 10,
            'offset' => 0,
        ]);

        $res->assertOk();
        $sourceIds = array_column(array_slice($res->json('data.actions'), 0, 3), 'sourceId');
        // Higher sourceId first for identical primary sort values.
        $expected = [$id1, $id2, $id3];
        rsort($expected);
        $this->assertSame($expected, $sourceIds);
    }

    /** @test */
    public function object_types_filter_still_returns_only_property_requests(): void
    {
        $this->requirePropertyRequestTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $this->createPropertyRequest($tenant->id);
        $this->mockUnreadPropertyRequestSourceIds([]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $actions = $res->json('data.actions');
        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        foreach ($actions as $action) {
            $this->assertSame('property_request', $action['objectType'] ?? null);
        }
    }
}
