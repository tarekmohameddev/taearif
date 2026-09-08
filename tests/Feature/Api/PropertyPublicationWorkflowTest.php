<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\PropertyExternalLink;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\PropertyAmenity;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\PropertySliderImg;
use App\Models\User\RealestateManagement\PropertySpecification;
use App\Models\User\RealestateManagement\UserFacade;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyPublicationWorkflowTest extends TestCase
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
        foreach ([
            'users',
            'user_properties',
            'user_property_contents',
            'memberships',
            'packages',
            'user_languages',
            'api_permissions',
            'api_model_has_permissions',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    private function seedTenantContext(User $tenant, int $propertyLimit = 100): Language
    {
        $package = Package::firstOrCreate(
            ['title' => 'Property Publication Workflow Package'],
            [
                'slug' => 'property-publication-workflow-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => $propertyLimit,
                'serial_number' => 992,
            ]
        );

        $package->update(['real_estate_limit_number' => $propertyLimit]);

        $membership = Membership::firstOrNew(['user_id' => $tenant->id]);
        $membership->status = 1;
        $membership->start_date = now()->subDay();
        $membership->expire_date = now()->addMonth();
        $membership->package_id = $package->id;
        $membership->price = 0;
        $membership->currency = 'USD';
        $membership->currency_symbol = '$';
        $membership->payment_method = 'test';
        $membership->transaction_id = 'property-publication-' . uniqid();
        $membership->save();

        MembershipCacheService::clearCache($tenant->id);

        ApiUserCategory::firstOrCreate(
            ['slug' => 'other'],
            [
                'name' => 'Other',
                'type' => 'property',
                'is_active' => 1,
            ]
        );

        return Language::firstOrCreate(
            ['user_id' => $tenant->id, 'is_default' => 1],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'rtl' => 1,
            ]
        );
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

    /**
     * @return array{0: User, 1: Language}
     */
    private function actingAsTenant(array $permissions, int $propertyLimit = 100): array
    {
        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'pubflow' . Str::random(6),
        ]);
        $language = $this->seedTenantContext($tenant, $propertyLimit);
        $this->grantPermissions($tenant, $tenant, $permissions);
        Sanctum::actingAs($tenant);

        return [$tenant, $language];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProperty(User $tenant, Language $language, array $overrides = [], array $contentOverrides = []): Property
    {
        $property = Property::create(array_merge([
            'user_id' => $tenant->id,
            'created_by' => $tenant->id,
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
            'featured_image' => 'properties/publication-test.jpg',
        ], $overrides));

        PropertyContent::create(array_merge([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => $language->id,
            'title' => 'Publication Property ' . $property->id,
            'slug' => 'publication-property-' . $property->id,
            'address' => 'Riyadh Address',
            'description' => 'Publication workflow description',
        ], $contentOverrides));

        return $property->fresh(['contents']);
    }

    private function skipIfMissingRelationTables(): void
    {
        foreach ([
            'user_amenities',
            'user_property_amenities',
            'user_property_slider_imgs',
            'user_property_specifications',
            'property_external_links',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_store_allows_minimal_incomplete_unpublished_draft_payload_with_omitted_optional_keys(): void
    {
        $this->skipIfMissingSchema();
        [$tenant] = $this->actingAsTenant(['properties.create']);

        $response = $this->postJson('/api/properties', [
            'publish_status' => 'draft',
            'status' => 0,
            'title' => 'Draft only title',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success');

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'user_id' => $tenant->id,
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'incomplete',
        ]);
        $property = Property::findOrFail($propertyId);
        $this->assertNull($property->price);
        $this->assertNull($property->area);
        $this->assertNull($property->latitude);
        $this->assertNull($property->longitude);
    }

    public function test_store_allows_incomplete_draft_even_when_complete_property_limit_is_reached(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenant(['properties.create'], 1);

        $this->createProperty($tenant, $language, [
            'publish_status' => 'published',
            'status' => 1,
            'completion_status' => 'complete',
        ]);

        $response = $this->postJson('/api/properties', [
            'publish_status' => 'draft',
            'status' => 0,
            'title' => 'Limit-safe incomplete draft',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success');

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'user_id' => $tenant->id,
            'publish_status' => 'draft',
            'completion_status' => 'incomplete',
        ]);
    }

    public function test_update_allows_saving_property_back_to_incomplete_draft(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenant(['properties.update']);
        $property = $this->createProperty($tenant, $language);

        $response = $this->postJson("/api/properties/{$property->id}", [
            'publish_status' => 'draft',
            'title' => '',
            'address' => '',
            'description' => '',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $property->refresh();
        $content = PropertyContent::where('property_id', $property->id)->where('language_id', $language->id)->first();

        $this->assertSame('draft', $property->publish_status);
        $this->assertSame('incomplete', $property->completion_status);
        $this->assertSame(0, (int) $property->status);
        $this->assertSame('', $content?->title);
        $this->assertSame('', $content?->address);
        $this->assertSame('', $content?->description);
    }

    public function test_partial_update_preserves_omitted_scalars_content_and_relations(): void
    {
        $this->skipIfMissingSchema();
        $this->skipIfMissingRelationTables();
        foreach (['user_facades', 'user_property_characteristics'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
        [$tenant, $language] = $this->actingAsTenant(['properties.update']);

        $property = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'complete',
            'price' => 500000,
            'area' => 120,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $english = Language::create([
            'user_id' => $tenant->id,
            'name' => 'English',
            'code' => 'en',
            'rtl' => 0,
            'is_default' => 0,
        ]);

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => $english->id,
            'title' => 'English title',
            'slug' => 'english-title-' . $property->id,
            'address' => 'English address',
            'description' => 'English description',
        ]);

        $amenity = Amenity::create([
            'user_id' => $tenant->id,
            'language_id' => $language->id,
            'name' => 'Pool',
            'slug' => 'pool',
            'icon' => 'pool',
            'status' => 1,
            'serial_number' => 1,
        ]);

        PropertyAmenity::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'amenity_id' => $amenity->id,
        ]);

        PropertySliderImg::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'image' => 'properties/gallery-preserved.jpg',
        ]);

        PropertySpecification::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => $language->id,
            'key' => 1,
            'label' => 'Facing',
            'value' => 'North',
        ]);

        PropertyExternalLink::create([
            'property_id' => $property->id,
            'user_id' => $tenant->id,
            'platform' => 'website',
            'url' => 'https://example.com/property',
            'label' => 'Website',
            'active' => true,
        ]);

        $facade = UserFacade::create([
            'name' => 'North Facing',
        ]);

        UserPropertyCharacteristic::create([
            'property_id' => $property->id,
            'facade_id' => $facade->id,
            'length' => 42.5,
        ]);

        $response = $this->postJson("/api/properties/{$property->id}", [
            'publish_status' => 'draft',
            'price' => 650000,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $property->refresh();
        $characteristics = UserPropertyCharacteristic::where('property_id', $property->id)->first();
        $defaultContent = PropertyContent::where('property_id', $property->id)->where('language_id', $language->id)->first();
        $secondaryContent = PropertyContent::where('property_id', $property->id)->where('language_id', $english->id)->first();

        $this->assertSame(650000.0, (float) $property->price);
        $this->assertSame(120.0, (float) $property->area);
        $this->assertSame(24.7136, (float) $property->latitude);
        $this->assertSame(46.6753, (float) $property->longitude);
        $this->assertNotNull($characteristics);
        $this->assertSame($facade->id, $characteristics?->facade_id);
        $this->assertSame(42.5, (float) $characteristics?->length);
        $this->assertSame('Publication Property ' . $property->id, $defaultContent?->title);
        $this->assertSame('Riyadh Address', $defaultContent?->address);
        $this->assertSame('Publication workflow description', $defaultContent?->description);
        $this->assertSame('English title', $secondaryContent?->title);
        $this->assertDatabaseHas('user_property_amenities', [
            'property_id' => $property->id,
            'amenity_id' => $amenity->id,
        ]);
        $this->assertSame(1, PropertyAmenity::where('property_id', $property->id)->count());
        $this->assertDatabaseHas('user_property_slider_imgs', [
            'property_id' => $property->id,
            'image' => 'properties/gallery-preserved.jpg',
        ]);
        $this->assertSame(1, PropertySliderImg::where('property_id', $property->id)->count());
        $this->assertDatabaseHas('user_property_specifications', [
            'property_id' => $property->id,
            'language_id' => $language->id,
            'key' => 1,
            'label' => 'Facing',
            'value' => 'North',
        ]);
        $this->assertSame(1, PropertySpecification::where('property_id', $property->id)->count());
        $this->assertDatabaseHas('property_external_links', [
            'property_id' => $property->id,
            'platform' => 'website',
            'url' => 'https://example.com/property',
            'label' => 'Website',
            'active' => 1,
        ]);
        $this->assertSame(1, PropertyExternalLink::where('property_id', $property->id)->count());
    }

    public function test_update_can_publish_complete_draft_without_erasing_existing_data(): void
    {
        $this->skipIfMissingSchema();
        $this->skipIfMissingRelationTables();
        [$tenant, $language] = $this->actingAsTenant(['properties.update']);

        $property = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'complete',
        ]);

        $english = Language::create([
            'user_id' => $tenant->id,
            'name' => 'English',
            'code' => 'en',
            'rtl' => 0,
            'is_default' => 0,
        ]);

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => $english->id,
            'title' => 'English publish title',
            'slug' => 'english-publish-title-' . $property->id,
            'address' => 'English publish address',
            'description' => 'English publish description',
        ]);

        PropertySliderImg::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'image' => 'properties/publish-gallery.jpg',
        ]);

        PropertyExternalLink::create([
            'property_id' => $property->id,
            'user_id' => $tenant->id,
            'platform' => 'website',
            'url' => 'https://example.com/publish',
            'label' => 'Publish link',
            'active' => true,
        ]);

        $response = $this->postJson("/api/properties/{$property->id}", [
            'publish_status' => 'published',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $property->refresh();
        $defaultContent = PropertyContent::where('property_id', $property->id)->where('language_id', $language->id)->first();
        $secondaryContent = PropertyContent::where('property_id', $property->id)->where('language_id', $english->id)->first();

        $this->assertSame('published', $property->publish_status);
        $this->assertSame('complete', $property->completion_status);
        $this->assertSame(1, (int) $property->status);
        $this->assertSame('Publication Property ' . $property->id, $defaultContent?->title);
        $this->assertSame('English publish title', $secondaryContent?->title);
        $this->assertDatabaseHas('user_property_slider_imgs', [
            'property_id' => $property->id,
            'image' => 'properties/publish-gallery.jpg',
        ]);
        $this->assertSame(1, PropertySliderImg::where('property_id', $property->id)->count());
        $this->assertDatabaseHas('property_external_links', [
            'property_id' => $property->id,
            'platform' => 'website',
            'url' => 'https://example.com/publish',
            'label' => 'Publish link',
            'active' => 1,
        ]);
        $this->assertSame(1, PropertyExternalLink::where('property_id', $property->id)->count());
    }

    public function test_drafts_scope_unpublished_includes_complete_unpublished_and_incomplete(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenant(['properties.view']);

        $incomplete = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'incomplete',
        ]);

        $completeUnpublished = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'complete',
        ]);

        $published = $this->createProperty($tenant, $language, [
            'publish_status' => 'published',
            'status' => 1,
            'completion_status' => 'complete',
        ]);

        $response = $this->getJson('/api/properties/drafts?scope=unpublished&per_page=50');
        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($incomplete->id, $ids);
        $this->assertContains($completeUnpublished->id, $ids);
        $this->assertNotContains($published->id, $ids);
    }

    public function test_index_with_published_filter_excludes_complete_drafts(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenant(['properties.view']);

        $published = $this->createProperty($tenant, $language, [
            'publish_status' => 'published',
            'status' => 1,
            'completion_status' => 'complete',
        ]);

        $completeDraft = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'complete',
        ]);

        $incompleteDraft = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'incomplete',
        ]);

        $response = $this->getJson('/api/properties?publish_status=published&per_page=50');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $ids = collect($response->json('data.properties'))->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertContains($published->id, $ids);
        $this->assertNotContains($completeDraft->id, $ids);
        $this->assertNotContains($incompleteDraft->id, $ids);
    }

    public function test_complete_draft_returns_laravel_style_validation_payload_for_missing_publish_fields(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenant(['properties.create']);

        $draft = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'incomplete',
            'featured_image' => null,
            'property_type' => null,
        ], [
            'title' => '',
            'address' => '',
            'description' => '',
        ]);

        $response = $this->postJson("/api/properties/drafts/{$draft->id}/complete", []);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Validation failed');

        $this->assertSame(
            ['title', 'address', 'description', 'featured_image', 'property_type'],
            $response->json('missing_fields')
        );
        $this->assertIsArray($response->json('errors'));
        $this->assertArrayHasKey('title', $response->json('errors'));
        $this->assertArrayHasKey('featured_image', $response->json('errors'));
    }

    public function test_complete_draft_publishes_immediately_on_success(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenant(['properties.create']);

        $draft = $this->createProperty($tenant, $language, [
            'publish_status' => 'draft',
            'status' => 0,
            'completion_status' => 'incomplete',
            'featured_image' => null,
            'property_type' => null,
        ], [
            'title' => 'Incomplete title',
            'address' => '',
            'description' => '',
        ]);

        $response = $this->postJson("/api/properties/drafts/{$draft->id}/complete", [
            'title' => 'Publish Ready Title',
            'address' => 'Publish Ready Address',
            'description' => 'Publish ready description that is long enough',
            'featured_image' => 'https://example.com/ready.jpg',
            'property_type' => 'residential',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $draft->refresh();
        $this->assertSame('published', $draft->publish_status);
        $this->assertSame('complete', $draft->completion_status);
        $this->assertSame(1, (int) $draft->status);
        $this->assertNotNull($draft->completed_at);
    }
}
