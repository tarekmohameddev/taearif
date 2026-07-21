<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestsListStatsExtraTest extends TestCase
{
    use DatabaseTransactions;

    private function requirePropertyRequestTable(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
    }

    private function createPropertyRequest(int $userId, array $overrides = []): int
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
        ], $overrides);

        // Some environments may not have this column; only set when present.
        if (array_key_exists('customers_hub_stage_id', $data) && !Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            unset($data['customers_hub_stage_id']);
        }
        if (array_key_exists('is_archived', $data) && !Schema::hasColumn('users_property_requests', 'is_archived')) {
            unset($data['is_archived']);
        }

        return (int) DB::table('users_property_requests')->insertGetId($data);
    }

    /** @test */
    public function list_includes_under_process_and_deal_stage_stats_keys(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        // Seed some property requests. Only deal-stage counts depend on customers_hub_stage_id.
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'deal_completed']);
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'deal_completed']);
        $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'deal_rejected']);

        // Create an archived request if column exists; should not be counted in underProcess.
        if (Schema::hasColumn('users_property_requests', 'is_archived')) {
            $this->createPropertyRequest($tenant->id, ['is_archived' => 1]);
        }

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $stats = $res->json('data.stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('underProcess', $stats);
        $this->assertArrayHasKey('dealClosed', $stats);
        $this->assertArrayHasKey('dealNotClosed', $stats);

        $this->assertIsInt($stats['underProcess']);
        $this->assertIsInt($stats['dealClosed']);
        $this->assertIsInt($stats['dealNotClosed']);

        if (Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            $this->assertSame(2, $stats['dealClosed']);
            $this->assertSame(1, $stats['dealNotClosed']);
        }
    }

    /** @test */
    public function today_stat_includes_property_requests_created_today_without_due_date(): void
    {
        $this->requirePropertyRequestTable();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $yesterday = now()->subDay();

        // Created today, no appointment/reminder — must count toward "today"
        $this->createPropertyRequest($tenant->id);

        // Created yesterday — must not count toward "today"
        $this->createPropertyRequest($tenant->id, [
            'created_at' => $yesterday,
            'updated_at' => $yesterday,
        ]);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'tab' => 'all',
            'objectTypes' => ['property_request'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $stats = $res->json('data.stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('today', $stats);
        $this->assertSame(1, $stats['today']);
    }
}

