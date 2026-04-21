<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use App\Models\CustomersHub\IgnoredCustomer;
use App\Domain\CustomersHub\Services\IgnoredCustomersService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for the CustomersHub ignore list feature:
 *  - CRUD endpoints (GET / POST / DELETE)
 *  - IgnoredCustomersService::isIgnored()
 *  - Blocking property request creation for ignored phones
 */
class IgnoredCustomersTest extends TestCase
{
    use DatabaseTransactions;

    // =========================================================================
    // Helpers
    // =========================================================================

    private function requireTables(): void
    {
        $required = ['customers_hub_ignored_customers', 'users_property_requests'];
        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    // =========================================================================
    // Service unit-style tests
    // =========================================================================

    /** @test */
    public function is_ignored_returns_false_when_list_is_empty(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        $service = app(IgnoredCustomersService::class);

        $this->assertFalse($service->isIgnored($tenant->id, '+966501234567'));
    }

    /** @test */
    public function is_ignored_returns_true_after_phone_is_added(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        $service = app(IgnoredCustomersService::class);

        $service->add($tenant->id, '+966501234567', null, null, null);

        // Same phone in different local format — normalizer must unify them
        $this->assertTrue($service->isIgnored($tenant->id, '0501234567'));
        $this->assertTrue($service->isIgnored($tenant->id, '+966501234567'));
        $this->assertTrue($service->isIgnored($tenant->id, '966501234567'));
    }

    /** @test */
    public function is_ignored_returns_true_after_customer_id_is_added(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        $service = app(IgnoredCustomersService::class);

        $service->add($tenant->id, null, 9999, null, null);

        $this->assertTrue($service->isIgnored($tenant->id, null, 9999));
        $this->assertFalse($service->isIgnored($tenant->id, null, 1234));
    }

    /** @test */
    public function is_ignored_is_scoped_to_tenant(): void
    {
        $this->requireTables();
        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $service = app(IgnoredCustomersService::class);

        $service->add($tenantA->id, '0501234567', null, null, null);

        $this->assertTrue($service->isIgnored($tenantA->id, '0501234567'));
        $this->assertFalse($service->isIgnored($tenantB->id, '0501234567'));
    }

    /** @test */
    public function remove_deletes_entry_and_is_ignored_returns_false(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        $service = app(IgnoredCustomersService::class);

        $entry = $service->add($tenant->id, '0501234567', null, null, null);
        $this->assertTrue($service->isIgnored($tenant->id, '0501234567'));

        $result = $service->remove($tenant->id, $entry->id);
        $this->assertTrue($result);
        $this->assertFalse($service->isIgnored($tenant->id, '0501234567'));
    }

    // =========================================================================
    // API endpoint tests
    // =========================================================================

    /** @test */
    public function store_endpoint_adds_phone_to_ignore_list(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/ignored-customers', [
            'phone'  => '0501234567',
            'reason' => 'Spam',
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.entry.phone_normalized', '966501234567');

        $this->assertDatabaseHas('customers_hub_ignored_customers', [
            'tenant_user_id'  => $tenant->id,
            'phone_normalized' => '966501234567',
        ]);
    }

    /** @test */
    public function store_endpoint_requires_phone_or_customer_id(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/ignored-customers', []);

        $res->assertStatus(422);
    }

    /** @test */
    public function index_endpoint_returns_paginated_list(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Add two entries
        DB::table('customers_hub_ignored_customers')->insert([
            ['tenant_user_id' => $tenant->id, 'phone_normalized' => '966501111111', 'customer_id' => null, 'reason' => null, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_user_id' => $tenant->id, 'phone_normalized' => '966502222222', 'customer_id' => null, 'reason' => null, 'created_by' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $res = $this->getJson('/api/v2/customers-hub/ignored-customers');

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.pagination.total', 2);
    }

    /** @test */
    public function destroy_endpoint_removes_entry(): void
    {
        $this->requireTables();
        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $id = (int) DB::table('customers_hub_ignored_customers')->insertGetId([
            'tenant_user_id'  => $tenant->id,
            'phone_normalized' => '966501234567',
            'customer_id'      => null,
            'reason'           => null,
            'created_by'       => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $res = $this->deleteJson("/api/v2/customers-hub/ignored-customers/{$id}");

        $res->assertOk()->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('customers_hub_ignored_customers', [
            'id'              => $id,
            'tenant_user_id'  => $tenant->id,
        ]);
    }

    /** @test */
    public function destroy_returns_404_for_entry_of_different_tenant(): void
    {
        $this->requireTables();
        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        Sanctum::actingAs($tenantB);

        $id = (int) DB::table('customers_hub_ignored_customers')->insertGetId([
            'tenant_user_id'  => $tenantA->id,
            'phone_normalized' => '966501234567',
            'customer_id'      => null,
            'reason'           => null,
            'created_by'       => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $res = $this->deleteJson("/api/v2/customers-hub/ignored-customers/{$id}");
        $res->assertStatus(404);
    }

    // =========================================================================
    // Block creation — property request store endpoint
    // =========================================================================

    /** @test */
    public function store_property_request_is_blocked_when_phone_is_ignored(): void
    {
        $this->requireTables();

        if (!Schema::hasTable('users')) {
            $this->markTestSkipped('users table required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // Put the phone on the ignore list
        app(IgnoredCustomersService::class)->add($tenant->id, '0501234567', null, null, null);

        // Attempt to create a property request via the authenticated endpoint
        $res = $this->postJson('/api/v1/property-requests', [
            'full_name' => 'Test Customer',
            'phone'     => '0501234567',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('error_code', 'CUSTOMER_IGNORED');

        // Verify nothing was inserted
        $this->assertEquals(
            0,
            DB::table('users_property_requests')
                ->where('user_id', $tenant->id)
                ->where('phone', '0501234567')
                ->count()
        );
    }
}
