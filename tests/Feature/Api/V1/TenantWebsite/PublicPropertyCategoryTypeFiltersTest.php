<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PublicPropertyCategoryTypeFiltersTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties', 'user_property_contents'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        $this->ensurePropertyStatusColumns();
    }

    public function test_multi_property_types_or_filter(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $residential = $this->createProperty($tenant->id, ['property_type' => 'residential']);
        $commercial = $this->createProperty($tenant->id, ['property_type' => 'commercial']);
        $this->createProperty($tenant->id, ['property_type' => 'agricultural']);
        $this->createProperty($tenant->id, ['property_type' => 'industrial']);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?property_types=residential,commercial"
        );

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(2, $ids);
        $this->assertTrue($ids->contains($residential->id));
        $this->assertTrue($ids->contains($commercial->id));
    }

    public function test_multi_category_ids_or_filter(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $cat1 = $this->createProperty($tenant->id, ['category_id' => 1]);
        $cat2 = $this->createProperty($tenant->id, ['category_id' => 2]);
        $this->createProperty($tenant->id, ['category_id' => 3]);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?category_ids=1,2"
        );

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(2, $ids);
        $this->assertTrue($ids->contains($cat1->id));
        $this->assertTrue($ids->contains($cat2->id));
    }

    public function test_singular_property_type_and_category_id_compat(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $match = $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'category_id' => 1,
        ]);
        $this->createProperty($tenant->id, [
            'property_type' => 'commercial',
            'category_id' => 1,
        ]);
        $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'category_id' => 2,
        ]);

        $typeResponse = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?property_type=residential"
        );
        $typeResponse->assertOk();
        $typeIds = collect($typeResponse->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($typeIds->contains($match->id));
        $this->assertCount(2, $typeIds);

        $catResponse = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?category_id=1"
        );
        $catResponse->assertOk();
        $catIds = collect($catResponse->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($catIds->contains($match->id));
        $this->assertCount(2, $catIds);
    }

    public function test_comma_separated_singular_category_id_acts_as_or(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $cat1 = $this->createProperty($tenant->id, ['category_id' => 1]);
        $cat2 = $this->createProperty($tenant->id, ['category_id' => 2]);
        $this->createProperty($tenant->id, ['category_id' => 3]);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?category_id=1,2"
        );

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(2, $ids);
        $this->assertTrue($ids->contains($cat1->id));
        $this->assertTrue($ids->contains($cat2->id));
    }

    public function test_plural_category_ids_wins_over_singular(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $cat2 = $this->createProperty($tenant->id, ['category_id' => 2]);
        $this->createProperty($tenant->id, ['category_id' => 1]);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?category_id=1&category_ids=2"
        );

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($cat2->id));
    }

    public function test_empty_or_omitted_property_types_applies_no_filter(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $a = $this->createProperty($tenant->id, ['property_type' => 'residential']);
        $b = $this->createProperty($tenant->id, ['property_type' => 'commercial']);

        $omitted = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties");
        $omitted->assertOk();
        $omittedIds = collect($omitted->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(2, $omittedIds);
        $this->assertTrue($omittedIds->contains($a->id));
        $this->assertTrue($omittedIds->contains($b->id));

        $empty = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties?property_types=");
        $empty->assertOk();
        $emptyIds = collect($empty->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(2, $emptyIds);
        $this->assertTrue($emptyIds->contains($a->id));
        $this->assertTrue($emptyIds->contains($b->id));
    }

    public function test_and_across_property_types_and_category_ids(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $match1 = $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'category_id' => 1,
        ]);
        $match2 = $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'category_id' => 2,
        ]);
        $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'category_id' => 3,
        ]);
        $this->createProperty($tenant->id, [
            'property_type' => 'commercial',
            'category_id' => 1,
        ]);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?property_types=residential&category_ids=1,2"
        );

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(2, $ids);
        $this->assertTrue($ids->contains($match1->id));
        $this->assertTrue($ids->contains($match2->id));
    }

    public function test_unknown_property_type_returns_empty_list(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->createProperty($tenant->id, ['property_type' => 'residential']);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?property_types=not-a-type"
        );

        $response->assertOk();
        $this->assertSame([], $response->json('properties'));
    }

    public function test_multi_filter_with_limit_paginates_filtered_set(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->createProperty($tenant->id, ['property_type' => 'residential']);
        $this->createProperty($tenant->id, ['property_type' => 'residential']);
        $this->createProperty($tenant->id, ['property_type' => 'commercial']);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?property_types=residential&limit=1"
        );

        $response->assertOk();
        $this->assertCount(1, $response->json('properties'));
        $this->assertSame(2, (int) $response->json('pagination.total'));
    }

    public function test_matching_type_but_unpublished_is_excluded_by_default(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $published = $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'publish_status' => 'published',
        ]);
        $this->createProperty($tenant->id, [
            'property_type' => 'residential',
            'publish_status' => 'draft',
        ]);

        $response = $this->getJson(
            "/api/v1/tenant-website/{$tenant->username}/properties?property_types=residential"
        );

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($published->id));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createProperty(int $userId, array $extra = []): Property
    {
        $categoryId = $extra['category_id'] ?? null;
        unset($extra['category_id']);

        $publishStatus = $extra['publish_status'] ?? 'published';
        unset($extra['publish_status']);

        $property = Property::create(array_merge([
            'user_id' => $userId,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => $publishStatus,
            'status' => $publishStatus === 'published' ? 1 : 0,
            'featured_image' => 'test.jpg',
            'property_type' => 'residential',
        ], $extra));

        PropertyContent::create([
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test ' . $property->id,
            'slug' => 'test-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
            'category_id' => $categoryId,
        ]);

        return $property->fresh(['contents']);
    }
}
