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
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyCreatorTest extends TestCase
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
        foreach (['users', 'user_properties', 'user_property_contents', 'memberships', 'packages', 'user_languages', 'api_permissions', 'api_model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! Schema::hasColumn('user_properties', 'created_by')) {
            $this->markTestSkipped('Missing created_by column on user_properties.');
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
            ['title' => 'Property Creator Test Package'],
            [
                'slug' => 'property-creator-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'real_estate_limit_number' => 100,
                'serial_number' => 996,
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
        $membership->transaction_id = 'property-creator-' . uniqid();
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
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Creator Test Unit ' . Str::random(6),
            'address' => 'Tower A',
            'description' => 'Unit description for creator test',
            'featured_image' => 'properties/creator-test-unit.jpg',
            'purpose' => 'sale',
            'property_type' => 'residential',
            'area' => 120,
            'price' => 500000,
            'status' => 1,
            'latitude' => 25.2048,
            'longitude' => 55.2708,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createCompleteProperty(User $tenant, User $creator, array $overrides = []): Property
    {
        $property = Property::create(array_merge([
            'user_id' => $tenant->id,
            'created_by' => $creator->id,
            'price' => 500000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'property_type' => 'residential',
            'area' => 120,
            'status' => 1,
            'completion_status' => 'complete',
            'featured' => 0,
            'featured_image' => 'properties/test.jpg',
        ], $overrides));

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::where('user_id', $tenant->id)->where('is_default', 1)->value('id') ?? 1,
            'title' => 'Unit ' . $property->id,
            'slug' => 'unit-' . $property->id,
            'address' => 'Riyadh',
            'description' => 'Test description',
        ]);

        return $property->fresh(['contents', 'creator']);
    }

    public function test_owner_create_sets_created_by_and_show_returns_creator_name(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'first_name' => 'Owner',
            'last_name' => 'User',
        ]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create', 'properties.view']);
        Sanctum::actingAs($tenant);

        $createResponse = $this->postJson('/api/properties', $this->validStorePayload());
        $createResponse->assertCreated();

        $propertyId = (int) $createResponse->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'user_id' => $tenant->id,
            'created_by' => $tenant->id,
        ]);

        $showResponse = $this->getJson("/api/properties/{$propertyId}");
        $showResponse->assertOk()
            ->assertJsonPath('data.property.creator.id', $tenant->id)
            ->assertJsonPath('data.property.creator.type', 'tenant');

        $this->assertNotEmpty($showResponse->json('data.property.creator.name'));
    }

    public function test_employee_create_sets_created_by_to_employee_and_user_id_to_tenant(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'first_name' => 'Sara',
            'last_name' => 'Ahmed',
        ]);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($employee, $tenant, ['properties.create', 'properties.view']);
        Sanctum::actingAs($employee);

        $createResponse = $this->postJson('/api/properties', $this->validStorePayload());
        $createResponse->assertCreated();

        $propertyId = (int) $createResponse->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'user_id' => $tenant->id,
            'created_by' => $employee->id,
        ]);
    }

    public function test_list_returns_creator_object(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'first_name' => 'List',
            'last_name' => 'Creator',
        ]);
        $property = $this->createCompleteProperty($tenant, $tenant);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $response = $this->getJson('/api/properties?per_page=50');
        $response->assertOk();

        $item = collect($response->json('data.properties'))->firstWhere('id', $property->id);
        $this->assertNotNull($item);
        $this->assertSame($tenant->id, $item['creator']['id']);
        $this->assertSame('tenant', $item['creator']['type']);
        $this->assertNotEmpty($item['creator']['name']);
    }

    public function test_client_cannot_override_created_by_on_create(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $otherUser = User::factory()->create(['account_type' => 'tenant']);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload([
            'created_by' => $otherUser->id,
        ]));
        $response->assertCreated();

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'created_by' => $tenant->id,
        ]);
        $this->assertDatabaseMissing('user_properties', [
            'id' => $propertyId,
            'created_by' => $otherUser->id,
        ]);
    }

    public function test_duplicate_sets_created_by_to_current_user(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $original = $this->createCompleteProperty($tenant, $tenant, [
            'featured_image' => 'properties/original.jpg',
        ]);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create', 'properties.view']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson("/api/properties/{$original->id}/duplicate", []);
        $response->assertCreated();

        $duplicateId = (int) $response->json('duplicated_property.id');
        $this->assertNotSame($original->id, $duplicateId);
        $this->assertDatabaseHas('user_properties', [
            'id' => $duplicateId,
            'user_id' => $tenant->id,
            'created_by' => $tenant->id,
        ]);
    }

    public function test_draft_show_returns_formatted_creator(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'first_name' => 'Draft',
            'last_name' => 'Owner',
        ]);

        $draft = Property::create([
            'user_id' => $tenant->id,
            'created_by' => $tenant->id,
            'price' => 100000,
            'purpose' => 'sale',
            'property_type' => 'residential',
            'area' => 100,
            'status' => 0,
            'completion_status' => 'incomplete',
            'featured' => 0,
            'featured_image' => 'properties/draft.jpg',
        ]);

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $draft->id,
            'language_id' => 1,
            'title' => 'Draft Unit',
            'slug' => 'draft-unit-' . $draft->id,
            'address' => 'Draft Address',
            'description' => 'Draft description',
        ]);

        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $response = $this->getJson("/api/properties/drafts/{$draft->id}");
        $response->assertOk()
            ->assertJsonPath('data.creator.id', $tenant->id)
            ->assertJsonPath('data.created_by.id', $tenant->id)
            ->assertJsonPath('data.creator.type', 'tenant');

        $this->assertNotEmpty($response->json('data.creator.name'));
    }

    public function test_public_property_show_hides_creator_fields(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createCompleteProperty($tenant, $tenant, [
            'publish_status' => 'published',
            'unit_status' => 'available',
        ]);
        $slug = $property->contents->first()->slug;

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}");
        $response->assertOk();

        $payload = json_encode($response->json('property'));
        $this->assertStringNotContainsString('"created_by"', $payload);
        $this->assertStringNotContainsString('"creator"', $payload);
    }
}
