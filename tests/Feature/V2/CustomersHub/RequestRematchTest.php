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

class RequestRematchTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['users_property_requests', 'property_matches'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
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
            'user_id'       => $userId,
            'full_name'     => 'Test Customer',
            'phone'         => '+966501234567',
            'is_active'     => 1,
            'is_read'       => 0,
            'is_ignored'    => 0,
            'source'        => 'whatsapp',
            'created_at'    => now(),
            'updated_at'    => now(),
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
    public function rematch_fails_when_request_is_ignored(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'is_ignored'    => 1,
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/rematch");

        $res->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    /** @test */
    public function rematch_fails_without_minimal_data(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        // No city, no property_type
        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/rematch");

        $res->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure(['errors' => ['minimal_missing_fields']]);
    }

    /** @test */
    public function rematch_calls_matching_service_when_minimal_data_present(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        $matchingMock = $this->mock(MatchingService::class);
        $matchingMock->shouldReceive('generateMatchesForRequest')
            ->once()
            ->with('web', $requestId, \Mockery::any(), \Mockery::any(), $tenant->id)
            ->andReturn([]);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/rematch");

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['request_id', 'matched_count', 'is_complete', 'message']]);
    }

    /** @test */
    public function rematch_returns_404_for_missing_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson('/api/v2/customers-hub/requests/property_request_99999/rematch')
            ->assertStatus(404);
    }

    /** @test */
    public function rematch_requires_authentication(): void
    {
        $this->postJson('/api/v2/customers-hub/requests/property_request_1/rematch')
            ->assertUnauthorized();
    }

    /** @test */
    public function rematch_scoped_to_tenant(): void
    {
        $this->requireTables();

        $tenant1 = $this->createTenant();
        $tenant2 = $this->createTenant();
        Sanctum::actingAs($tenant1);

        $requestId = $this->createPropertyRequest($tenant2->id, [
            'city'          => 'الرياض',
            'property_type' => 'apartment',
        ]);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/rematch")
            ->assertStatus(404);
    }
}
