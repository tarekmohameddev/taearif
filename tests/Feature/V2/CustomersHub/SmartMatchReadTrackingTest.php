<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmartMatchReadTrackingTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTrackingTables(): void
    {
        foreach (['users_property_requests', 'property_request_smart_match_runs', 'property_request_smart_match_reads'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required. Run migrations.");
            }
        }
    }

    private function createRequest(int $tenantId): int
    {
        $data = [
            'user_id' => $tenantId,
            'full_name' => 'Smart Match Reader Test',
            'phone' => '+966500000001',
            'is_active' => 1,
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('users_property_requests', 'status_id')) {
            $statusId = DB::table('property_request_statuses')->where('is_active', true)->value('id');
            if ($statusId !== null) {
                $data['status_id'] = $statusId;
            }
        }

        return (int) DB::table('users_property_requests')->insertGetId($data);
    }

    private function createRun(int $tenantId, int $requestId, int $matchCount = 1): int
    {
        return (int) DB::table('property_request_smart_match_runs')->insertGetId([
            'tenant_id' => $tenantId,
            'property_request_id' => $requestId,
            'match_count' => $matchCount,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function marking_latest_match_run_read_is_account_scoped_and_idempotent(): void
    {
        $this->requireTrackingTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee = User::factory()->create(['account_type' => 'employee', 'tenant_id' => $tenant->id]);
        $requestId = $this->createRequest($tenant->id);
        $runId = $this->createRun($tenant->id, $requestId);

        Sanctum::actingAs($employee);
        $endpoint = "/api/v2/customers-hub/requests/property_request_{$requestId}/matches/read";
        $this->patchJson($endpoint)
            ->assertOk()
            ->assertJsonPath('data.is_read_by_current_account', true);
        $this->patchJson($endpoint)
            ->assertOk()
            ->assertJsonPath('data.is_read_by_current_account', true);

        $this->assertDatabaseHas('property_request_smart_match_reads', [
            'tenant_id' => $tenant->id,
            'property_request_id' => $requestId,
            'account_user_id' => $employee->id,
            'last_read_match_run_id' => $runId,
        ]);
        $this->assertDatabaseMissing('property_request_smart_match_reads', [
            'tenant_id' => $tenant->id,
            'property_request_id' => $requestId,
            'account_user_id' => $tenant->id,
        ]);
        $this->assertDatabaseHas('users_property_requests', ['id' => $requestId, 'is_read' => 0]);
    }

    /** @test */
    public function a_new_run_becomes_unread_and_another_account_has_independent_state(): void
    {
        $this->requireTrackingTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $employee = User::factory()->create(['account_type' => 'employee', 'tenant_id' => $tenant->id]);
        $requestId = $this->createRequest($tenant->id);
        $firstRunId = $this->createRun($tenant->id, $requestId);

        Sanctum::actingAs($tenant);
        $endpoint = "/api/v2/customers-hub/requests/property_request_{$requestId}/matches/read";
        $this->patchJson($endpoint)->assertOk()->assertJsonPath('data.is_read_by_current_account', true);
        $secondRunId = $this->createRun($tenant->id, $requestId, 0);
        $this->assertGreaterThan($firstRunId, $secondRunId);

        Sanctum::actingAs($tenant);
        $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ])->assertOk()
            ->assertJsonPath('data.actions.0.smartMatch.hasBeenRun', true)
            ->assertJsonPath('data.actions.0.smartMatch.hasMatches', false)
            ->assertJsonPath('data.actions.0.smartMatch.isReadByCurrentAccount', false);

        Sanctum::actingAs($employee);
        $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ])->assertOk()
            ->assertJsonPath('data.actions.0.smartMatch.isReadByCurrentAccount', false);

        $this->assertDatabaseHas('property_request_smart_match_reads', [
            'tenant_id' => $tenant->id,
            'property_request_id' => $requestId,
            'account_user_id' => $tenant->id,
            'last_read_match_run_id' => $firstRunId,
        ]);
    }

    /** @test */
    public function read_endpoint_returns_false_when_no_run_exists_and_rejects_cross_tenant_requests(): void
    {
        $this->requireTrackingTables();
        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $otherTenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $requestId = $this->createRequest($tenant->id);

        Sanctum::actingAs($tenant);
        $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches/read")
            ->assertOk()
            ->assertJsonPath('data.is_read_by_current_account', false)
            ->assertJsonPath('data.read_at', null);

        Sanctum::actingAs($otherTenant);
        $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/matches/read")
            ->assertNotFound();
    }
}
