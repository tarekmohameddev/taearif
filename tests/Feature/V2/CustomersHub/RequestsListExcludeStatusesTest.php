<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestsListExcludeStatusesTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('api_customers') || !Schema::hasTable('reminders')) {
            $this->markTestSkipped('api_customers and reminders tables required.');
        }
    }

    private function seedCustomerAndReminders(int $tenantId): array
    {
        $now = now();

        $customerId = (int) DB::table('api_customers')->insertGetId([
            'user_id' => $tenantId,
            'name' => 'Test Customer',
            'phone_number' => '+966500000001',
            'password' => bcrypt('password'),
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => null,
        ]);

        $pendingId = (int) DB::table('reminders')->insertGetId([
            'user_id' => $tenantId,
            'customer_id' => $customerId,
            'title' => 'Pending reminder',
            'description' => 'Pending',
            'priority' => 1,
            'datetime' => $now->copy()->addDay(),
            'status' => 'pending',
            'source' => 'manual',
            'snoozed_until' => null,
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $completedId = (int) DB::table('reminders')->insertGetId([
            'user_id' => $tenantId,
            'customer_id' => $customerId,
            'title' => 'Completed reminder',
            'description' => 'Completed',
            'priority' => 1,
            'datetime' => $now->copy()->subDay(),
            'status' => 'completed',
            'source' => 'manual',
            'snoozed_until' => null,
            'deleted_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'customerId' => $customerId,
            'pendingReminderId' => $pendingId,
            'completedReminderId' => $completedId,
        ];
    }

    /** @test */
    public function exclude_statuses_only_excludes_matching_items(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $this->seedCustomerAndReminders($tenant->id);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['reminder'],
            'excludeStatuses' => ['completed'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $actions = $res->json('data.actions') ?? [];
        $statuses = array_values(array_unique(array_column($actions, 'status')));
        sort($statuses);

        $this->assertSame(['pending'], $statuses);
    }

    /** @test */
    public function statuses_only_includes_matching_items(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $this->seedCustomerAndReminders($tenant->id);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['reminder'],
            'statuses' => ['completed'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $actions = $res->json('data.actions') ?? [];
        $statuses = array_values(array_unique(array_column($actions, 'status')));
        sort($statuses);

        $this->assertSame(['completed'], $statuses);
    }

    /** @test */
    public function statuses_and_exclude_statuses_together_apply_both_filters(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $this->seedCustomerAndReminders($tenant->id);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['reminder'],
            'statuses' => ['pending', 'completed'],
            'excludeStatuses' => ['completed'],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $actions = $res->json('data.actions') ?? [];
        $statuses = array_values(array_unique(array_column($actions, 'status')));
        sort($statuses);

        $this->assertSame(['pending'], $statuses);
    }

    /** @test */
    public function empty_exclude_statuses_is_ignored(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        Sanctum::actingAs($tenant);

        $this->seedCustomerAndReminders($tenant->id);

        $res = $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['reminder'],
            'statuses' => ['pending', 'completed'],
            'excludeStatuses' => [],
            'limit' => 50,
            'offset' => 0,
        ]);

        $res->assertOk()->assertJsonPath('status', 'success');

        $actions = $res->json('data.actions') ?? [];
        $statuses = array_values(array_unique(array_column($actions, 'status')));
        sort($statuses);

        $this->assertSame(['completed', 'pending'], $statuses);
    }
}

