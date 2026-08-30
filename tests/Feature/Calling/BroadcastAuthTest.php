<?php

namespace Tests\Feature\Calling;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'pusher',
            'broadcasting.connections.pusher.key' => 'test-key',
            'broadcasting.connections.pusher.secret' => 'test-secret',
            'broadcasting.connections.pusher.app_id' => '1234',
            'broadcasting.connections.pusher.options.cluster' => 'mt1',
        ]);
    }

    /** @test */
    public function test_employee_can_authorize_own_tenant_channel_via_api_prefix(): void
    {
        [$tenant, $employee] = $this->seedUsers();

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/broadcasting/auth', [
                'socket_id'    => '1234.5678',
                'channel_name' => "private-tenant.{$tenant->id}",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    /** @test */
    public function test_employee_can_authorize_own_tenant_channel_via_root_path(): void
    {
        [$tenant, $employee] = $this->seedUsers();

        $this->actingAs($employee, 'sanctum')
            ->postJson('/broadcasting/auth', [
                'socket_id'    => '1234.5678',
                'channel_name' => "private-tenant.{$tenant->id}",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    /** @test */
    public function test_employee_cannot_authorize_another_tenant_channel(): void
    {
        [, $employee] = $this->seedUsers();
        $otherTenant = \App\Models\User::factory()->create(['account_type' => 'tenant']);

        $this->actingAs($employee, 'sanctum')
            ->postJson('/api/broadcasting/auth', [
                'socket_id'    => '1234.5678',
                'channel_name' => "private-tenant.{$otherTenant->id}",
            ])
            ->assertForbidden();
    }

    /** @test */
    public function test_tenant_owner_can_authorize_own_channel(): void
    {
        [$tenant] = $this->seedUsers();

        $this->actingAs($tenant, 'sanctum')
            ->postJson('/api/broadcasting/auth', [
                'socket_id'    => '1234.5678',
                'channel_name' => "private-tenant.{$tenant->id}",
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    private function seedUsers(): array
    {
        $tenant = \App\Models\User::factory()->create(['account_type' => 'tenant']);
        $employee = \App\Models\User::factory()->create([
            'account_type' => 'employee',
            'tenant_id'    => $tenant->id,
        ]);

        return [$tenant, $employee];
    }
}
