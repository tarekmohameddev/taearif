<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestCompleteDataTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
        if (!Schema::hasColumn('users_property_requests', 'is_ignored')) {
            $this->markTestSkipped('is_ignored column required. Run migration.');
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
            'is_ignored' => 0,
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

    /** @test */
    public function complete_data_updates_city_and_property_type(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'city'          => 'جدة',
            'property_type' => 'villa',
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['request_id', 'has_minimal_data', 'minimal_missing_fields', 'is_complete', 'missing_fields', 'message'],
            ]);

        $this->assertTrue($res->json('data.has_minimal_data'));

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('جدة', $row->city);
        $this->assertEquals('villa', $row->property_type);
    }

    /** @test */
    public function complete_data_maps_purpose_buy_to_sale(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'purpose' => 'buy',
            'city'    => 'الرياض',
        ])->assertOk();

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('sale', $row->purpose);
    }

    /** @test */
    public function complete_data_updates_budget_fields(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'budget_from' => 500000,
            'budget_to'   => 1000000,
            'currency'    => 'SAR',
        ])->assertOk();

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals(500000, (float) $row->budget_from);
        $this->assertEquals(1000000, (float) $row->budget_to);
        $this->assertEquals('SAR', $row->currency);
    }

    /** @test */
    public function complete_data_rejects_invalid_field_values(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'latitude' => 999,  // out of range
        ]);

        $res->assertUnprocessable();
    }

    /** @test */
    public function complete_data_returns_404_for_non_existent_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/property_request_99999/complete-data', [
            'city' => 'الرياض',
        ]);

        $res->assertStatus(404);
    }

    /** @test */
    public function complete_data_rejects_non_property_request_ids(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/reminder_1/complete-data', [
            'city' => 'الرياض',
        ]);

        $res->assertStatus(404);
    }

    /** @test */
    public function complete_data_only_updates_provided_fields(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        // Only send district, city should not be overwritten
        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'district' => 'حي النزهة',
        ])->assertOk();

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('الرياض', $row->city);
        $this->assertEquals('حي النزهة', $row->district);
    }

    /** @test */
    public function complete_data_requires_authentication(): void
    {
        $res = $this->postJson('/api/v2/customers-hub/requests/property_request_1/complete-data', [
            'city' => 'الرياض',
        ]);
        $res->assertUnauthorized();
    }
}
