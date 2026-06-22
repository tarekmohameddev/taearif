<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\User;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PublicProjectFiltersTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'users',
            'user_projects',
            'user_project_contents',
            'user_properties',
            'user_property_contents',
            'api_user_categories',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        $this->ensurePropertyStatusColumns();
    }

    public function test_can_filter_projects_by_complete_status(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);

        $finished = $this->createProject($tenant->id, 1);
        $this->createProject($tenant->id, 0);
        $this->createProject($tenant->id, 2);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?status=1");
        $response->assertOk();

        $ids = collect($response->json('projects'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($finished->id));
    }

    public function test_can_filter_projects_by_unit_category_slug(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);

        $duplexCategory = $this->ensureCategory('duplex', 'Duplex');
        $villaCategory = $this->ensureCategory('villa', 'Villa');

        $projectWithDuplex = $this->createProject($tenant->id, 1);
        $projectWithVilla = $this->createProject($tenant->id, 0);

        $this->createProperty($tenant->id, $projectWithDuplex->id, $duplexCategory->id, 'duplex');
        $this->createProperty($tenant->id, $projectWithVilla->id, $villaCategory->id, 'villa');

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?unit_category=duplex");
        $response->assertOk();

        $ids = collect($response->json('projects'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $this->assertTrue($ids->contains($projectWithDuplex->id));
        $this->assertFalse($ids->contains($projectWithVilla->id));
    }

    public function test_can_filter_projects_by_min_units(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $category = $this->ensureCategory('apartment', 'Apartment');

        $twoUnitsProject = $this->createProject($tenant->id, 1);
        $singleUnitProject = $this->createProject($tenant->id, 1);

        $this->createProperty($tenant->id, $twoUnitsProject->id, $category->id, 'apartment');
        $this->createProperty($tenant->id, $twoUnitsProject->id, $category->id, 'apartment');
        $this->createProperty($tenant->id, $singleUnitProject->id, $category->id, 'apartment');

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?min_units=2");
        $response->assertOk();

        $ids = collect($response->json('projects'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $this->assertTrue($ids->contains($twoUnitsProject->id));
        $this->assertFalse($ids->contains($singleUnitProject->id));
    }

    public function test_project_filter_options_are_dynamic(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $duplexCategory = $this->ensureCategory('duplex', 'Duplex');
        $villaCategory = $this->ensureCategory('villa', 'Villa');

        $finishedProject = $this->createProject($tenant->id, 1);
        $notFinishedProject = $this->createProject($tenant->id, 0);

        $this->createProperty($tenant->id, $finishedProject->id, $duplexCategory->id, 'duplex');
        $this->createProperty($tenant->id, $finishedProject->id, $duplexCategory->id, 'duplex');
        $this->createProperty($tenant->id, $notFinishedProject->id, $villaCategory->id, 'villa');

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects/filter-options");
        $response->assertOk();

        $this->assertSame(2, (int) $response->json('filters.projects_total'));
        $this->assertSame(1, (int) $response->json('filters.units_range.min'));
        $this->assertSame(2, (int) $response->json('filters.units_range.max'));

        $statuses = collect($response->json('filters.complete_statuses'))->keyBy('value');
        $this->assertSame(1, (int) ($statuses->get(1)['count'] ?? 0));
        $this->assertSame(1, (int) ($statuses->get(0)['count'] ?? 0));
        $this->assertSame(0, (int) ($statuses->get(2)['count'] ?? 0));

        $categories = collect($response->json('filters.unit_categories'))->keyBy('slug');
        $this->assertSame(2, (int) ($categories->get('duplex')['units_count'] ?? 0));
        $this->assertSame(1, (int) ($categories->get('duplex')['projects_count'] ?? 0));
        $this->assertSame(1, (int) ($categories->get('villa')['units_count'] ?? 0));

        $this->assertArrayHasKey('listing_purposes', $response->json('filters'));
        $this->assertArrayHasKey('unit_statuses', $response->json('filters'));
        $this->assertArrayHasKey('price_range', $response->json('filters'));
    }

    public function test_can_filter_projects_by_price_range(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);

        $affordable = $this->createProject($tenant->id, 1, ['min_price' => 100000, 'max_price' => 200000]);
        $this->createProject($tenant->id, 1, ['min_price' => 500000, 'max_price' => 600000]);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?price_from=150000&price_to=250000");
        $response->assertOk();

        $ids = collect($response->json('projects'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($affordable->id));
        $this->assertCount(1, $ids);
    }

    public function test_can_filter_projects_by_listing_purpose(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $category = $this->ensureCategory('apartment', 'Apartment');

        $saleProject = $this->createProject($tenant->id, 1);
        $rentProject = $this->createProject($tenant->id, 1);

        $this->createProperty($tenant->id, $saleProject->id, $category->id, 'apartment', ['listing_purpose' => 'sale']);
        $this->createProperty($tenant->id, $rentProject->id, $category->id, 'apartment', ['listing_purpose' => 'rent']);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?listing_purpose=sale");
        $response->assertOk();

        $ids = collect($response->json('projects'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($saleProject->id));
        $this->assertFalse($ids->contains($rentProject->id));
    }

    public function test_can_filter_projects_by_unit_status(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $category = $this->ensureCategory('apartment', 'Apartment');

        $availableProject = $this->createProject($tenant->id, 1);
        $soldProject = $this->createProject($tenant->id, 1);

        $this->createProperty($tenant->id, $availableProject->id, $category->id, 'apartment', ['unit_status' => 'available']);
        $this->createProperty($tenant->id, $soldProject->id, $category->id, 'apartment', ['unit_status' => 'sold']);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?unit_status=available");
        $response->assertOk();

        $ids = collect($response->json('projects'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($availableProject->id));
        $this->assertFalse($ids->contains($soldProject->id));
    }

    public function test_can_search_projects_by_query(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);

        $matching = $this->createProject($tenant->id, 1, ['title' => 'Sunset Towers']);
        $this->createProject($tenant->id, 1, ['title' => 'Green Valley']);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?q=Sunset");
        $response->assertOk();

        $ids = collect($response->json('projects'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($matching->id));
        $this->assertCount(1, $ids);
    }

    public function test_can_sort_projects_by_price_asc(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);

        $expensive = $this->createProject($tenant->id, 1, ['min_price' => 500000, 'max_price' => 600000]);
        $cheap = $this->createProject($tenant->id, 1, ['min_price' => 100000, 'max_price' => 200000]);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?sort=price_asc");
        $response->assertOk();

        $ids = collect($response->json('projects'))->pluck('id')->map(fn ($id) => (int) $id)->values();
        $this->assertSame($cheap->id, $ids->first());
        $this->assertSame($expensive->id, $ids->last());
    }

    private function ensureCategory(string $slug, string $name): ApiUserCategory
    {
        return ApiUserCategory::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'type' => 'property',
                'is_active' => true,
            ]
        );
    }

    private function createProject(int $userId, int $completeStatus, array $overrides = []): Project
    {
        $payload = [
            'user_id' => $userId,
            'featured_image' => 'project.jpg',
            'complete_status' => $completeStatus,
        ];

        if (Schema::hasColumn('user_projects', 'featured')) {
            $payload['featured'] = 0;
        }
        if (Schema::hasColumn('user_projects', 'published')) {
            $payload['published'] = 1;
        }
        if (Schema::hasColumn('user_projects', 'units')) {
            $payload['units'] = 0;
        }
        if (Schema::hasColumn('user_projects', 'min_price') && array_key_exists('min_price', $overrides)) {
            $payload['min_price'] = $overrides['min_price'];
        }
        if (Schema::hasColumn('user_projects', 'max_price') && array_key_exists('max_price', $overrides)) {
            $payload['max_price'] = $overrides['max_price'];
        }
        if (Schema::hasColumn('user_projects', 'developer') && array_key_exists('developer', $overrides)) {
            $payload['developer'] = $overrides['developer'];
        }

        $project = Project::create($payload);

        ProjectContent::create([
            'user_id' => $userId,
            'project_id' => $project->id,
            'language_id' => 1,
            'title' => $overrides['title'] ?? ('Project ' . $project->id),
            'slug' => $overrides['slug'] ?? ('project-' . $project->id),
            'address' => $overrides['address'] ?? 'Address',
            'description' => 'Description',
        ]);

        return $project->fresh(['contents']);
    }

    private function createProperty(int $userId, int $projectId, int $categoryId, string $propertyType, array $overrides = []): Property
    {
        $payload = [
            'user_id' => $userId,
            'project_id' => $projectId,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => $overrides['listing_purpose'] ?? 'sale',
            'unit_status' => $overrides['unit_status'] ?? 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'test.jpg',
            'area' => 120,
            'category_id' => $categoryId,
        ];

        if (Schema::hasColumn('user_properties', 'property_type')) {
            $payload['property_type'] = $propertyType;
        } elseif (Schema::hasColumn('user_properties', 'type')) {
            $payload['type'] = $propertyType;
        }

        $property = Property::create($payload);

        $contentPayload = [
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Property ' . $property->id,
            'slug' => 'property-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
        ];

        if (Schema::hasColumn('user_property_contents', 'category_id')) {
            $contentPayload['category_id'] = $categoryId;
        }
        if (Schema::hasColumn('user_property_contents', 'city_id')) {
            $contentPayload['city_id'] = 1;
        }

        PropertyContent::create($contentPayload);

        return $property->fresh(['contents']);
    }
}
