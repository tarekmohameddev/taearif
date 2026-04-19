<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompleteAndDismissTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
        if (!Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
            $this->markTestSkipped('customers_hub_stage_id column required. Run migration.');
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
            'is_archived' => 0,
            'source'     => 'whatsapp',
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

    private function createInquiry(int $userId, array $overrides = []): int
    {
        $defaults = [
            'user_id'    => $userId,
            'customer_id' => $this->createCustomer($userId)->id,
            'message'    => 'Test inquiry',
            'is_read'    => 0,
            'is_archived' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('api_customer_inquiry')->insertGetId(array_merge($defaults, $overrides));
    }

    private function createCustomer(int $userId): object
    {
        return DB::table('api_customers')->insertGetId([
            'user_id'    => $userId,
            'name'       => 'Test Customer',
            'phone_number' => '+966501234567',
            'created_at' => now(),
            'updated_at' => now(),
        ]) ? (object) DB::table('api_customers')->where('id', DB::table('api_customers')->max('id'))->first() : null;
    }

    /** @test */
    public function complete_property_request_sets_stage_to_deal_completed(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'new_lead']);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete");

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message', 'Action completed successfully');

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('deal_completed', $row->customers_hub_stage_id);
        $this->assertEquals(1, $row->is_read);
        $this->assertEquals(0, $row->is_archived);
    }

    /** @test */
    public function dismiss_property_request_sets_stage_to_deal_rejected(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['customers_hub_stage_id' => 'new_lead']);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/dismiss", [
            'reason' => 'Customer not interested',
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message', 'Action dismissed successfully');

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('deal_rejected', $row->customers_hub_stage_id);
        $this->assertEquals(1, $row->is_archived);
    }

    /** @test */
    public function complete_inquiry_sets_stage_to_deal_completed(): void
    {
        $this->requireTables();

        if (!Schema::hasTable('api_customer_inquiry')) {
            $this->markTestSkipped('api_customer_inquiry table required.');
        }

        if (!Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
            $this->markTestSkipped('stage_id column required on api_customer_inquiry.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $inquiryId = $this->createInquiry($tenant->id, ['stage_id' => 'new_lead']);

        $res = $this->postJson("/api/v2/customers-hub/requests/inquiry_{$inquiryId}/complete");

        $res->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('api_customer_inquiry')->where('id', $inquiryId)->first();
        $this->assertEquals('deal_completed', $row->stage_id);
        $this->assertEquals(1, $row->is_read);
        $this->assertEquals(0, $row->is_archived);
    }

    /** @test */
    public function dismiss_inquiry_sets_stage_to_deal_rejected(): void
    {
        $this->requireTables();

        if (!Schema::hasTable('api_customer_inquiry')) {
            $this->markTestSkipped('api_customer_inquiry table required.');
        }

        if (!Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
            $this->markTestSkipped('stage_id column required on api_customer_inquiry.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $inquiryId = $this->createInquiry($tenant->id, ['stage_id' => 'new_lead']);

        $res = $this->postJson("/api/v2/customers-hub/requests/inquiry_{$inquiryId}/dismiss", [
            'reason' => 'Not interested',
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success');

        $row = DB::table('api_customer_inquiry')->where('id', $inquiryId)->first();
        $this->assertEquals('deal_rejected', $row->stage_id);
        $this->assertEquals(1, $row->is_archived);
    }

    /** @test */
    public function complete_updates_timestamp(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);
        $beforeTime = now();

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete");

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $afterTime = now();

        $this->assertTrue($beforeTime <= $row->updated_at && $row->updated_at <= $afterTime);
    }

    /** @test */
    public function dismiss_updates_timestamp(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);
        $beforeTime = now();

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/dismiss", [
            'reason' => 'Test',
        ]);

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $afterTime = now();

        $this->assertTrue($beforeTime <= $row->updated_at && $row->updated_at <= $afterTime);
    }

    /** @test */
    public function complete_requires_authentication(): void
    {
        $this->postJson('/api/v2/customers-hub/requests/property_request_1/complete')
            ->assertUnauthorized();
    }

    /** @test */
    public function dismiss_requires_authentication(): void
    {
        $this->postJson('/api/v2/customers-hub/requests/property_request_1/dismiss', [
            'reason' => 'Test',
        ])->assertUnauthorized();
    }

    /** @test */
    public function dismiss_requires_reason(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/dismiss", []);

        $res->assertStatus(422);
        $res->assertJsonPath('status', 'error');
    }

    /** @test */
    public function complete_returns_404_for_nonexistent_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson("/api/v2/customers-hub/requests/property_request_99999/complete")
            ->assertStatus(404);
    }

    /** @test */
    public function dismiss_returns_404_for_nonexistent_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson("/api/v2/customers-hub/requests/property_request_99999/dismiss", [
            'reason' => 'Test',
        ])->assertStatus(404);
    }

    /** @test */
    public function complete_is_scoped_to_tenant(): void
    {
        $this->requireTables();

        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        Sanctum::actingAs($tenant1);

        $requestId = $this->createPropertyRequest($tenant2->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete")
            ->assertStatus(404);
    }

    /** @test */
    public function dismiss_is_scoped_to_tenant(): void
    {
        $this->requireTables();

        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        Sanctum::actingAs($tenant1);

        $requestId = $this->createPropertyRequest($tenant2->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/dismiss", [
            'reason' => 'Test',
        ])->assertStatus(404);
    }
}
