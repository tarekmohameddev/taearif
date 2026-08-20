<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyDraftDeleteTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePropertyStatusColumns();
    }

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_properties', 'user_property_contents', 'memberships', 'packages', 'user_languages', 'api_permissions', 'api_model_has_permissions', 'property_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
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

    private function seedTenantContext(User $tenant): void
    {
        $package = Package::firstOrCreate(
            ['title' => 'Property Draft Delete Test Package'],
            [
                'slug' => 'property-draft-delete-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'real_estate_limit_number' => 100,
                'serial_number' => 997,
            ]
        );

        $membership = Membership::firstOrNew(['user_id' => $tenant->id]);
        $membership->status = 1;
        $membership->start_date = now()->subDay();
        $membership->expire_date = now()->addMonth();
        $membership->package_id = $package->id;
        $membership->price = 0;
        $membership->currency = 'USD';
        $membership->currency_symbol = '$';
        $membership->payment_method = 'test';
        $membership->transaction_id = 'property-draft-delete-' . uniqid();
        $membership->save();

        MembershipCacheService::clearCache($tenant->id);

        Language::firstOrCreate(
            ['user_id' => $tenant->id, 'is_default' => 1],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'rtl' => 1,
            ]
        );

        ApiUserCategory::firstOrCreate(
            ['slug' => 'other'],
            [
                'name' => 'Other',
                'type' => 'property',
                'is_active' => 1,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProperty(User $tenant, User $creator, array $overrides = []): Property
    {
        $property = Property::create(array_merge([
            'user_id' => $tenant->id,
            'created_by' => $creator->id,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'draft',
            'property_type' => 'residential',
            'area' => 100,
            'status' => 0,
            'completion_status' => 'incomplete',
            'featured' => 0,
            'featured_image' => 'properties/draft-delete.jpg',
        ], $overrides));

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::where('user_id', $tenant->id)->where('is_default', 1)->value('id') ?? 1,
            'title' => 'Draft ' . $property->id,
            'slug' => 'draft-delete-' . $property->id,
            'address' => 'Riyadh',
            'description' => 'Draft description',
        ]);

        return $property->fresh();
    }

    public function test_destroy_draft_deletes_incomplete_owned_draft(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $draft = $this->createProperty($tenant, $tenant);

        Sanctum::actingAs($tenant);

        $response = $this->deleteJson("/api/properties/drafts/{$draft->id}");
        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('user_properties', ['id' => $draft->id]);
    }

    public function test_destroy_draft_returns_404_for_complete_property(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $property = $this->createProperty($tenant, $tenant, [
            'completion_status' => 'complete',
            'status' => 1,
            'publish_status' => 'published',
        ]);

        Sanctum::actingAs($tenant);

        $this->deleteJson("/api/properties/drafts/{$property->id}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Draft property not found');

        $this->assertDatabaseHas('user_properties', ['id' => $property->id]);
    }

    public function test_destroy_draft_returns_404_for_other_tenant_draft(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $other = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->seedTenantContext($other);
        $foreignDraft = $this->createProperty($other, $other);

        Sanctum::actingAs($tenant);

        $this->deleteJson("/api/properties/drafts/{$foreignDraft->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('user_properties', ['id' => $foreignDraft->id]);
    }

    public function test_destroy_draft_forbidden_without_delete_permission(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        $this->seedTenantContext($tenant);
        $draft = $this->createProperty($tenant, $employee);
        $this->grantPermissions($employee, $tenant, ['properties.view']);

        Sanctum::actingAs($employee);

        $this->deleteJson("/api/properties/drafts/{$draft->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('user_properties', ['id' => $draft->id]);
    }

    public function test_bulk_destroy_drafts_partial_success_without_exists_oracle(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $other = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->seedTenantContext($other);

        $ownDraft = $this->createProperty($tenant, $tenant);
        $complete = $this->createProperty($tenant, $tenant, [
            'completion_status' => 'complete',
            'status' => 1,
            'publish_status' => 'published',
        ]);
        $foreignDraft = $this->createProperty($other, $other);

        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/properties/drafts/bulk-delete', [
            'property_ids' => [$ownDraft->id, $complete->id, $foreignDraft->id],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.deleted_count', 1)
            ->assertJsonPath('data.failed_count', 2);

        $this->assertDatabaseMissing('user_properties', ['id' => $ownDraft->id]);
        $this->assertDatabaseHas('user_properties', ['id' => $complete->id]);
        $this->assertDatabaseHas('user_properties', ['id' => $foreignDraft->id]);
    }

    public function test_bulk_destroy_drafts_rejects_oversize_payload(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        Sanctum::actingAs($tenant);

        $ids = range(1, 101);
        $this->postJson('/api/properties/drafts/bulk-delete', [
            'property_ids' => $ids,
        ])->assertStatus(422);
    }

    public function test_generic_destroy_blocks_other_tenant_property(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $other = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->seedTenantContext($other);
        $foreign = $this->createProperty($other, $other, [
            'completion_status' => 'complete',
            'status' => 1,
            'publish_status' => 'published',
        ]);

        Sanctum::actingAs($tenant);

        $this->deleteJson("/api/properties/{$foreign->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('user_properties', ['id' => $foreign->id]);
    }

    public function test_generic_destroy_deletes_own_property(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $property = $this->createProperty($tenant, $tenant, [
            'completion_status' => 'complete',
            'status' => 1,
            'publish_status' => 'published',
        ]);

        Sanctum::actingAs($tenant);

        $this->deleteJson("/api/properties/{$property->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('user_properties', ['id' => $property->id]);
    }
}
