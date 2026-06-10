<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Support\AuditContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

/**
 * Smoke tests for DEV-186 — mirrors manual QA in front-end-integration-DEV-186.md §10.
 */
class EntityAuditLogSmokeTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties', 'entity_audit_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_smoke_tenant_owner_lists_audit_logs_with_status_change_filter(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Smoke test',
        ])->assertOk();

        $this->getJson("/api/properties/{$property->id}/audit-logs?action=status_change")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'logs' => [['id', 'action', 'field_name', 'old_value', 'new_value', 'changed_by', 'changed_at']],
                    'pagination' => ['total', 'per_page', 'current_page', 'last_page'],
                ],
            ])
            ->assertJsonFragment([
                'action' => 'status_change',
                'field_name' => 'unit_status',
                'old_value' => 'available',
                'new_value' => 'sold',
                'reason' => 'Smoke test',
            ]);
    }

    public function test_smoke_tenant_owner_filters_audit_logs_by_deed_number(): void
    {
        $tenant = $this->actingAsTenant();
        $property = $this->createProperty($tenant->id, ['deed_number' => 'OLD-DEED']);

        AuditContext::set($tenant->id, 'tenant', $tenant->id, '127.0.0.1', 'smoke');
        $property->deed_number = 'NEW-DEED';
        $property->save();

        $this->getJson("/api/properties/{$property->id}/audit-logs?field_name=deed_number")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment([
                'field_name' => 'deed_number',
                'old_value' => 'OLD-DEED',
                'new_value' => 'NEW-DEED',
            ]);
    }

    public function test_smoke_tenant_owner_can_use_with_actor_on_audit_logs(): void
    {
        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'first_name' => 'Smoke',
            'last_name' => 'Owner',
            'email' => 'smoke-owner@example.com',
        ]);
        Sanctum::actingAs($tenant);

        $property = $this->createProperty($tenant->id, ['deed_number' => 'A']);

        AuditContext::set($tenant->id, 'tenant', $tenant->id, '127.0.0.1', 'smoke');
        $property->deed_number = 'B';
        $property->save();

        $this->getJson("/api/properties/{$property->id}/audit-logs?with_actor=1&field_name=deed_number")
            ->assertOk()
            ->assertJsonPath('data.logs.0.changed_by.name', 'Smoke Owner')
            ->assertJsonPath('data.logs.0.changed_by.email', 'smoke-owner@example.com');
    }

    public function test_smoke_agent_employee_gets_403_on_audit_logs(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $agent = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $agentPermissions = config('rbac.role_templates.agent', []);
        $this->grantPermissions($agent, $tenant, $agentPermissions);
        Sanctum::actingAs($agent);

        $property = $this->createProperty($tenant->id);

        $this->getJson("/api/properties/{$property->id}/audit-logs")
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    private function actingAsTenant(): User
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProperty(int $userId, array $overrides = []): Property
    {
        return Property::create(array_merge([
            'user_id' => $userId,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ], $overrides));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Throwable $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $user->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }
}
