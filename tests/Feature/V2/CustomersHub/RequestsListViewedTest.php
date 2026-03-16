<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestsListViewedTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePropertyRequestTables(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
    }

    private function requireListViewedTable(): void
    {
        if (!Schema::hasTable('customers_hub_requests_list_viewed')) {
            $this->markTestSkipped('customers_hub_requests_list_viewed table required. Run migration.');
        }
    }

    private function createPropertyRequest(int $userId, ?array $overrides = null): int
    {
        $data = array_merge([
            'full_name' => 'Test Requester',
            'phone' => '+966501234567',
            'user_id' => $userId,
            'region' => 'الرياض',
            'is_active' => 1,
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides ?? []);
        if (Schema::hasColumn('users_property_requests', 'status_id')) {
            $statusId = DB::table('property_request_statuses')->where('is_active', true)->value('id');
            if ($statusId !== null) {
                $data['status_id'] = $statusId;
            }
        }
        $id = DB::table('users_property_requests')->insertGetId($data);
        return (int) $id;
    }

    /** @test */
    public function mark_viewed_returns_200_and_stores_viewed_at(): void
    {
        $this->requireListViewedTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/mark-viewed');

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['viewedAt']]);

        $viewedAt = $res->json('data.viewedAt');
        $this->assertNotEmpty($viewedAt);

        $row = DB::table('customers_hub_requests_list_viewed')
            ->where('user_id', $tenant->id)
            ->first();
        $this->assertNotNull($row);
        $this->assertNotNull($row->viewed_at);
    }

    /** @test */
    public function list_actions_include_is_updated_boolean(): void
    {
        $this->requirePropertyRequestTables();
        $this->requireListViewedTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');
        $actions = $res->json('data.actions');
        $this->assertIsArray($actions);

        foreach ($actions as $action) {
            $this->assertArrayHasKey('isUpdated', $action);
            $this->assertIsBool($action['isUpdated']);
        }
    }

    /** @test */
    public function is_updated_true_when_request_updated_after_viewed(): void
    {
        $this->requirePropertyRequestTables();
        $this->requireListViewedTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $resList = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);
        $resList->assertOk();
        $actions = $resList->json('data.actions');
        $this->assertNotEmpty($actions);

        $resViewed = $this->postJson('/api/v2/customers-hub/requests/mark-viewed');
        $resViewed->assertOk();
        $viewedAt = Carbon::parse($resViewed->json('data.viewedAt'));

        DB::table('users_property_requests')
            ->where('id', $requestId)
            ->where('user_id', $tenant->id)
            ->update(['updated_at' => $viewedAt->copy()->addSeconds(2)]);

        $resList2 = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);
        $resList2->assertOk();
        $actions2 = $resList2->json('data.actions');
        $action = collect($actions2)->firstWhere('sourceId', $requestId);
        $this->assertNotNull($action, 'Created request should appear in list');
        $this->assertTrue($action['isUpdated'], 'Request updated after mark-viewed should have isUpdated true');
    }

    /** @test */
    public function is_updated_false_for_new_request_created_after_viewed(): void
    {
        $this->requirePropertyRequestTables();
        $this->requireListViewedTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $this->postJson('/api/v2/customers-hub/requests/mark-viewed')->assertOk();

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);
        $res->assertOk();
        $actions = $res->json('data.actions');
        $action = collect($actions)->firstWhere('sourceId', $requestId);
        $this->assertNotNull($action);
        $this->assertFalse($action['isUpdated'], 'New request created after mark-viewed should have isUpdated false');
    }

    /** @test */
    public function mark_viewed_is_per_viewer_employee_has_own_row(): void
    {
        $this->requireListViewedTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson('/api/v2/customers-hub/requests/mark-viewed')->assertOk();

        Sanctum::actingAs($employee);
        $this->postJson('/api/v2/customers-hub/requests/mark-viewed')->assertOk();

        $tenantRow = DB::table('customers_hub_requests_list_viewed')->where('user_id', $tenant->id)->first();
        $employeeRow = DB::table('customers_hub_requests_list_viewed')->where('user_id', $employee->id)->first();
        $this->assertNotNull($tenantRow);
        $this->assertNotNull($employeeRow);
        $this->assertNotSame($tenantRow->user_id, $employeeRow->user_id);
    }
}
