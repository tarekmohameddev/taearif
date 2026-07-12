<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
use App\Models\User\UserDistrict;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectLocationTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_projects', 'user_project_contents', 'user_districts', 'api_permissions', 'api_model_has_permissions', 'memberships', 'packages', 'user_languages'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! Schema::hasColumn('user_projects', 'city_id') || ! Schema::hasColumn('user_projects', 'state_id')) {
            $this->markTestSkipped('Missing city_id/state_id columns on user_projects. Run migrations.');
        }
    }

    private function grantPermissions(User $tenant, array $permissions): void
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

            $tenant->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    private function seedTenantContext(User $tenant): Language
    {
        $package = Package::firstOrCreate(
            ['title' => 'Project Location Test Package'],
            [
                'slug' => 'project-location-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
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
        $membership->transaction_id = 'project-location-' . uniqid();
        $membership->save();

        MembershipCacheService::clearCache($tenant->id);

        return Language::firstOrCreate(
            ['user_id' => $tenant->id, 'is_default' => 1],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'rtl' => 1,
            ]
        );
    }

    private function createDistrict(int $cityId = 101): UserDistrict
    {
        return UserDistrict::query()->create([
            'name_ar' => 'حي الاختبار',
            'name_en' => 'Test District',
            'city_id' => $cityId,
            'city_name_ar' => 'الرياض',
            'city_name_en' => 'Riyadh',
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
        ]);
    }

    private function projectPayload(Language $language, array $overrides = []): array
    {
        return array_merge([
            'featured_image' => 'projects/test.jpg',
            'contents' => [
                [
                    'language_id' => $language->id,
                    'title' => 'Location Test Project',
                    'description' => 'Project description for location testing',
                    'address' => 'Test address',
                    'meta_keyword' => null,
                    'meta_description' => null,
                ],
            ],
        ], $overrides);
    }

    private function setupTenant(array $permissions = ['projects.create', 'projects.view', 'projects.update']): array
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $language = $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $permissions);
        Sanctum::actingAs($tenant);

        return [$tenant, $language];
    }

    public function test_create_project_with_district_persists_location(): void
    {
        $this->skipIfMissingSchema();

        [$tenant, $language] = $this->setupTenant();
        $district = $this->createDistrict();

        $response = $this->postJson('/api/projects', $this->projectPayload($language, [
            'district_id' => $district->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('user_project.state_id', $district->id)
            ->assertJsonPath('user_project.city_id', $district->city_id)
            ->assertJsonPath('user_project.district_id', $district->id);

        $this->assertDatabaseHas('user_projects', [
            'user_id' => $tenant->id,
            'state_id' => $district->id,
            'city_id' => $district->city_id,
        ]);
    }

    public function test_create_project_without_district_leaves_location_null(): void
    {
        $this->skipIfMissingSchema();

        [$tenant, $language] = $this->setupTenant();

        $response = $this->postJson('/api/projects', $this->projectPayload($language));

        $response->assertCreated();

        $projectId = (int) $response->json('user_project.id');
        $this->assertDatabaseHas('user_projects', [
            'id' => $projectId,
            'user_id' => $tenant->id,
            'state_id' => null,
            'city_id' => null,
        ]);
    }

    public function test_update_project_changes_district_and_city(): void
    {
        $this->skipIfMissingSchema();

        [$tenant, $language] = $this->setupTenant();
        $districtA = $this->createDistrict(201);
        $districtB = $this->createDistrict(202);

        $create = $this->postJson('/api/projects', $this->projectPayload($language, [
            'district_id' => $districtA->id,
        ]))->assertCreated();

        $projectId = (int) $create->json('user_project.id');

        $this->postJson("/api/projects/{$projectId}", $this->projectPayload($language, [
            'district_id' => $districtB->id,
        ]))->assertOk()
            ->assertJsonPath('user_project.state_id', $districtB->id)
            ->assertJsonPath('user_project.city_id', $districtB->city_id)
            ->assertJsonPath('user_project.district_id', $districtB->id);

        $this->assertDatabaseHas('user_projects', [
            'id' => $projectId,
            'state_id' => $districtB->id,
            'city_id' => $districtB->city_id,
        ]);
    }

    public function test_create_rejects_city_id_mismatch_with_district(): void
    {
        $this->skipIfMissingSchema();

        [, $language] = $this->setupTenant();
        $district = $this->createDistrict();

        $this->postJson('/api/projects', $this->projectPayload($language, [
            'district_id' => $district->id,
            'city_id' => $district->city_id + 1,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors(['city_id']);
    }

    public function test_state_id_alias_works_like_district_id(): void
    {
        $this->skipIfMissingSchema();

        [, $language] = $this->setupTenant();
        $district = $this->createDistrict();

        $this->postJson('/api/projects', $this->projectPayload($language, [
            'state_id' => $district->id,
        ]))->assertCreated()
            ->assertJsonPath('user_project.state_id', $district->id)
            ->assertJsonPath('user_project.district_id', $district->id);
    }

    public function test_admin_show_returns_saved_location_fields(): void
    {
        $this->skipIfMissingSchema();

        [, $language] = $this->setupTenant();
        $district = $this->createDistrict();

        $projectId = (int) $this->postJson('/api/projects', $this->projectPayload($language, [
            'district_id' => $district->id,
        ]))->json('user_project.id');

        $this->getJson("/api/projects/{$projectId}")
            ->assertOk()
            ->assertJsonPath('data.project.city_id', $district->city_id)
            ->assertJsonPath('data.project.state_id', $district->id)
            ->assertJsonPath('data.project.district_id', $district->id);
    }

    public function test_tenant_website_project_detail_returns_location_fields(): void
    {
        $this->skipIfMissingSchema();

        [$tenant, $language] = $this->setupTenant();
        $district = $this->createDistrict();

        $project = Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/public.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Test Developer',
            'units' => 5,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
            'city_id' => $district->city_id,
            'state_id' => $district->id,
        ]);

        ProjectContent::query()->create([
            'user_id' => $tenant->id,
            'project_id' => $project->id,
            'language_id' => $language->id,
            'title' => 'Public Project',
            'slug' => 'public-project-location',
            'address' => 'Public address',
            'description' => 'Public project description',
        ]);

        $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects/public-project-location")
            ->assertOk()
            ->assertJsonPath('project.location.city_id', $district->city_id)
            ->assertJsonPath('project.location.district_id', $district->id)
            ->assertJsonPath('project.location.state_id', $district->id)
            ->assertJsonPath('project.location.city', 'الرياض')
            ->assertJsonPath('project.location.district', 'حي الاختبار');
    }

    public function test_tenant_website_can_filter_projects_by_district_id(): void
    {
        $this->skipIfMissingSchema();

        [$tenant, $language] = $this->setupTenant();
        $districtA = $this->createDistrict(301);
        $districtB = $this->createDistrict(302);

        $projectA = Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/a.jpg',
            'min_price' => 100000,
            'published' => 1,
            'developer' => 'Dev A',
            'city_id' => $districtA->city_id,
            'state_id' => $districtA->id,
        ]);
        $projectB = Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/b.jpg',
            'min_price' => 200000,
            'published' => 1,
            'developer' => 'Dev B',
            'city_id' => $districtB->city_id,
            'state_id' => $districtB->id,
        ]);

        foreach ([$projectA, $projectB] as $index => $project) {
            ProjectContent::query()->create([
                'user_id' => $tenant->id,
                'project_id' => $project->id,
                'language_id' => $language->id,
                'title' => 'Project ' . $index,
                'slug' => 'project-location-filter-' . $index,
                'address' => 'Address ' . $index,
                'description' => 'Description ' . $index,
            ]);
        }

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/projects?district_id={$districtA->id}");

        $response->assertOk();

        $ids = collect($response->json('projects'))->pluck('id')->map(fn ($id) => (int) $id)->values();
        $this->assertTrue($ids->contains($projectA->id));
        $this->assertFalse($ids->contains($projectB->id));
    }
}
