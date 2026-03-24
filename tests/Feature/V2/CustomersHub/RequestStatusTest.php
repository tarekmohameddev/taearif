<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use App\Services\Matching\MatchingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestStatusTest extends TestCase
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
    public function mark_read_sets_is_read_true(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['is_read' => 0]);

        $res = $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/read");

        $res->assertOk()->assertJsonPath('status', 'success');

        $this->assertEquals(1, DB::table('users_property_requests')->where('id', $requestId)->value('is_read'));
    }

    /** @test */
    public function mark_unread_sets_is_read_false(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['is_read' => 1]);

        $res = $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/unread");

        $res->assertOk()->assertJsonPath('status', 'success');

        $this->assertEquals(0, DB::table('users_property_requests')->where('id', $requestId)->value('is_read'));
    }

    /** @test */
    public function ignore_sets_is_ignored_true(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/ignore", [
            'is_ignored' => true,
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.is_ignored', true);

        $this->assertEquals(1, DB::table('users_property_requests')->where('id', $requestId)->value('is_ignored'));
    }

    /** @test */
    public function ignore_with_false_un_ignores_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['is_ignored' => 1]);

        $res = $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/ignore", [
            'is_ignored' => false,
        ]);

        $res->assertOk()
            ->assertJsonPath('data.is_ignored', false);

        $this->assertEquals(0, DB::table('users_property_requests')->where('id', $requestId)->value('is_ignored'));
    }

    /** @test */
    public function ignore_defaults_to_true_when_body_empty(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/ignore")
            ->assertOk()
            ->assertJsonPath('data.is_ignored', true);

        $this->assertEquals(1, DB::table('users_property_requests')->where('id', $requestId)->value('is_ignored'));
    }

    /** @test */
    public function ignored_request_does_not_trigger_matching_on_update(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, ['is_ignored' => 1]);

        // Mock MatchingService to ensure it is never called
        $matchingMock = $this->mock(MatchingService::class);
        $matchingMock->shouldNotReceive('generateMatchesForRequest');

        // Trigger an update via complete-data endpoint (observer would normally fire)
        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'city' => 'الرياض',
        ])->assertOk();
    }

    /** @test */
    public function status_endpoints_return_404_for_missing_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->patchJson('/api/v2/customers-hub/requests/property_request_99999/read')->assertStatus(404);
        $this->patchJson('/api/v2/customers-hub/requests/property_request_99999/unread')->assertStatus(404);
        $this->patchJson('/api/v2/customers-hub/requests/property_request_99999/ignore')->assertStatus(404);
    }

    /** @test */
    public function status_endpoints_require_authentication(): void
    {
        $this->patchJson('/api/v2/customers-hub/requests/property_request_1/read')->assertUnauthorized();
        $this->patchJson('/api/v2/customers-hub/requests/property_request_1/unread')->assertUnauthorized();
        $this->patchJson('/api/v2/customers-hub/requests/property_request_1/ignore')->assertUnauthorized();
    }

    /** @test */
    public function status_endpoints_scoped_to_tenant(): void
    {
        $this->requireTables();

        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        Sanctum::actingAs($tenant1);

        $requestId = $this->createPropertyRequest($tenant2->id);

        $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/read")->assertStatus(404);
        $this->patchJson("/api/v2/customers-hub/requests/property_request_{$requestId}/ignore")->assertStatus(404);
    }
}
