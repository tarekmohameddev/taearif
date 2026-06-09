<?php

namespace Tests\Feature\Api;

use App\Models\Api\EmployeeActivityLog;
use App\Models\ApiCustomer;
use App\Models\Logs\PropertyLog;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyStatusChangeTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties', 'property_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_forbidden_without_change_status_permission(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertForbidden();
    }

    public function test_sale_unit_cannot_be_marked_rented(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id, 'sale', 'available');

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'rented',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['unit_status']);
    }

    public function test_rent_unit_cannot_be_marked_sold(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id, 'rent', 'available');

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['unit_status']);
    }

    public function test_reserved_status_requires_customer_id(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'reserved',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_status_change_updates_unit_status(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk()
            ->assertJsonPath('data.unit_status', 'sold')
            ->assertJsonPath('data.listing_purpose', 'sale');
    }

    public function test_status_change_writes_audit_log(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk();

        $log = PropertyLog::where('property_id', $property->id)
            ->where('action', 'status_change')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('available', $log->changes['old_status'] ?? null);
        $this->assertSame('sold', $log->changes['new_status'] ?? null);
        $this->assertSame('Deal closed', $log->reason);
        $this->assertNull(
            PropertyLog::where('property_id', $property->id)->where('action', 'updated')->first()
        );
    }

    public function test_status_change_creates_team_activity_log(): void
    {
        if (! Schema::hasTable('api_employee_activity_logs')) {
            $this->markTestSkipped('api_employee_activity_logs table not available.');
        }

        if (! Schema::hasTable('user_property_contents')) {
            $this->markTestSkipped('user_property_contents table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createPropertyWithContent($user);
        $propertyTitle = 'Test ' . $property->id;

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk();

        $activity = EmployeeActivityLog::query()
            ->where('user_id', $user->id)
            ->where('action', 'property.status_changed')
            ->where('target_id', $property->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('user', $activity->actor_type);
        $this->assertSame($user->id, $activity->actor_id);
        $this->assertSame('property', $activity->target_type);
        $this->assertSame('available', $activity->old_values['unit_status'] ?? null);
        $this->assertSame($propertyTitle, $activity->old_values['property_name'] ?? null);
        $this->assertSame('sold', $activity->new_values['unit_status'] ?? null);
        $this->assertSame($propertyTitle, $activity->new_values['property_name'] ?? null);
        $this->assertSame('Deal closed', $activity->new_values['reason'] ?? null);
    }

    public function test_employee_status_change_activity_log(): void
    {
        if (! Schema::hasTable('api_employee_activity_logs')) {
            $this->markTestSkipped('api_employee_activity_logs table not available.');
        }

        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['properties.change_status']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($employee->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Closed by employee',
        ])->assertOk();

        $activity = EmployeeActivityLog::query()
            ->where('user_id', $tenant->id)
            ->where('action', 'property.status_changed')
            ->where('target_id', $property->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('employee', $activity->actor_type);
        $this->assertSame($employee->id, $activity->actor_id);
        $this->assertSame('sold', $activity->new_values['unit_status'] ?? null);
    }

    public function test_activity_log_visible_via_logs_api(): void
    {
        if (! Schema::hasTable('api_employee_activity_logs')) {
            $this->markTestSkipped('api_employee_activity_logs table not available.');
        }

        if (! Schema::hasTable('user_property_contents')) {
            $this->markTestSkipped('user_property_contents table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createPropertyWithContent($user);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk();

        $this->getJson('/api/v1/logs?action=property.status_changed&with_actor=1')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonFragment([
                'action' => 'property.status_changed',
                'target_id' => $property->id,
                'actor_id' => $user->id,
            ]);

        $logs = collect($this->getJson('/api/v1/logs?action=property.status_changed&with_actor=1')->json('data.logs'));
        $entry = $logs->firstWhere('target_id', $property->id);
        $this->assertNotNull($entry);
        $this->assertArrayHasKey('actor', $entry);
        $this->assertSame($user->id, $entry['actor']['id'] ?? null);
    }

    public function test_reserved_status_stores_customer_and_returns_in_response(): void
    {
        if (! Schema::hasTable('api_customers')) {
            $this->markTestSkipped('api_customers table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $customer = ApiCustomer::create([
            'user_id' => $user->id,
            'name' => 'Ahmed Ali',
            'phone_number' => '+966500000001',
            'password' => bcrypt('password'),
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'reserved',
            'customer_id' => $customer->id,
            'reason' => 'Deposit received',
        ])->assertOk()
            ->assertJsonPath('data.unit_status', 'reserved')
            ->assertJsonPath('data.customer.id', $customer->id)
            ->assertJsonPath('data.customer.name', 'Ahmed Ali');

        $log = PropertyLog::where('property_id', $property->id)
            ->where('action', 'status_change')
            ->first();

        $this->assertSame($customer->id, $log->changes['customer_id'] ?? null);

        if (Schema::hasTable('api_customer_assigned_property')) {
            $this->assertDatabaseHas('api_customer_assigned_property', [
                'customer_id' => $customer->id,
                'property_id' => $property->id,
            ]);
        }
    }

    public function test_reserved_status_rejects_other_tenant_customer_id(): void
    {
        if (! Schema::hasTable('api_customers')) {
            $this->markTestSkipped('api_customers table not available.');
        }

        $user = $this->actingAsTenant();
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($user->id);
        $otherCustomer = ApiCustomer::create([
            'user_id' => $otherTenant->id,
            'name' => 'Other Tenant Customer',
            'phone_number' => '+966500000002',
            'password' => bcrypt('password'),
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'reserved',
            'customer_id' => $otherCustomer->id,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);
    }

    public function test_employee_can_change_status_on_employee_owned_property(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['properties.change_status']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($employee->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Closed by employee',
        ])->assertOk()
            ->assertJsonPath('data.unit_status', 'sold');
    }

    public function test_public_list_reflects_updated_unit_status(): void
    {
        if (! Schema::hasTable('user_property_contents')) {
            $this->markTestSkipped('user_property_contents table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createPropertyWithContent($user);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk();

        $response = $this->getJson("/api/v1/tenant-website/{$user->username}/properties");
        $response->assertOk();

        $item = collect($response->json('properties'))->firstWhere('id', (string) $property->id);
        $this->assertNotNull($item);
        $this->assertSame('sold', $item['unit_status'] ?? null);
        $this->assertSame('published', $item['publish_status'] ?? null);
    }

    private function actingAsTenant(): User
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Sanctum::actingAs($user);

        return $user;
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

    private function createProperty(
        int $userId,
        string $listingPurpose = 'sale',
        string $unitStatus = 'available'
    ): Property {
        return Property::create([
            'user_id' => $userId,
            'price' => 1,
            'purpose' => $listingPurpose,
            'listing_purpose' => $listingPurpose,
            'unit_status' => $unitStatus,
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ]);
    }

    private function createPropertyWithContent(User $user): Property
    {
        $property = $this->createProperty($user->id);

        PropertyContent::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test ' . $property->id,
            'slug' => 'test-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
        ]);

        return $property->fresh(['contents']);
    }
}
