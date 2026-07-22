<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\Api\UserPropertyRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regression test for the Customers Hub caching bug: newly created/updated
 * property requests did not appear in /api/v2/customers-hub/requests/list
 * until the underlying 30-60s TTL caches expired.
 *
 * These tests deliberately prime the caches with an initial list() call and
 * then mutate data via Eloquent (mirroring ApiPropertyRequestController::store())
 * and via the update/complete/dismiss endpoints, asserting the very next
 * list() call (well inside the old TTL window) reflects the change.
 */
class RequestsListCacheInvalidationTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePropertyRequestTable(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function newly_created_property_request_appears_immediately_in_list(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Prime the list/stats/count caches with an empty result set.
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res->assertOk();
        $this->assertEquals(0, $res->json('data.pagination.total'));

        // Create a property request the same way ApiPropertyRequestController::store() does:
        // via the Eloquent model (triggers the saved() cache-busting hook).
        UserPropertyRequest::create([
            'full_name' => 'New Requester',
            'phone' => '+966501234567',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);

        // Well within the old 30-60s TTL window: the new request must be visible now.
        $res2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res2->assertOk();
        $this->assertEquals(1, $res2->json('data.pagination.total'));
        $actions = $res2->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('property_request', $actions[0]['objectType']);
    }

    /** @test */
    public function completing_a_request_is_immediately_reflected_in_list(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $propertyRequest = UserPropertyRequest::create([
            'full_name' => 'Requester To Complete',
            'phone' => '+966501234568',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);

        // Prime the cache with the request in its initial (pending) stage.
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res->assertOk();
        $this->assertNotEquals('deal_completed', $res->json('data.actions.0.customers_hub_stage_id'));

        $completeRes = $this->postJson(
            "/api/v2/customers-hub/requests/property_request_{$propertyRequest->id}/complete"
        );
        $completeRes->assertOk();

        // Immediately re-list: must reflect the completed stage, not the stale cached copy.
        $res2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res2->assertOk();
        $actions = $res2->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('deal_completed', $actions[0]['customers_hub_stage_id']);
    }

    /** @test */
    public function dismissing_a_request_is_immediately_reflected_in_list(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $propertyRequest = UserPropertyRequest::create([
            'full_name' => 'Requester To Dismiss',
            'phone' => '+966501234569',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);

        // Prime the cache.
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res->assertOk();

        $dismissRes = $this->postJson(
            "/api/v2/customers-hub/requests/property_request_{$propertyRequest->id}/dismiss",
            ['reason' => 'Not a fit']
        );
        $dismissRes->assertOk();

        $res2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res2->assertOk();
        $actions = $res2->json('data.actions');
        $this->assertCount(1, $actions);
        $this->assertEquals('deal_rejected', $actions[0]['customers_hub_stage_id']);
    }

    /** @test */
    public function moving_a_request_to_deal_completed_updates_stats_immediately(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $propertyRequest = UserPropertyRequest::create([
            'full_name' => 'Requester To Close',
            'phone' => '+966501234570',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);

        // Prime the list/stats caches (including the 120s ch_global_counts_* cache)
        // with the request in its initial (pending) stage.
        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res->assertOk();
        $this->assertEquals(0, $res->json('data.stats.dealClosed'));

        $moveRes = $this->postJson('/api/v2/customers-hub/pipeline/move', [
            'requestId' => $propertyRequest->id,
            'newStageId' => 'deal_completed',
        ]);
        $moveRes->assertOk();

        // Well within the old 120s TTL window: dealClosed must reflect the move now.
        $res2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res2->assertOk();
        $this->assertEquals(1, $res2->json('data.stats.dealClosed'));
    }

    /** @test */
    public function bulk_moving_requests_to_deal_completed_updates_stats_immediately(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestOne = UserPropertyRequest::create([
            'full_name' => 'Bulk Requester One',
            'phone' => '+966501234571',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);
        $requestTwo = UserPropertyRequest::create([
            'full_name' => 'Bulk Requester Two',
            'phone' => '+966501234572',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res->assertOk();
        $this->assertEquals(0, $res->json('data.stats.dealClosed'));

        $bulkMoveRes = $this->postJson('/api/v2/customers-hub/pipeline/bulk-move', [
            'requestIds' => [$requestOne->id, $requestTwo->id],
            'newStageId' => 'deal_completed',
        ]);
        $bulkMoveRes->assertOk();

        $res2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'limit' => 50,
            'offset' => 0,
        ]);
        $res2->assertOk();
        $this->assertEquals(2, $res2->json('data.stats.dealClosed'));
    }
}
