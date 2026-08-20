<?php

declare(strict_types=1);

namespace Tests\Feature\V2\CustomersHub;

use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestCompleteDataTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
        if (!Schema::hasColumn('users_property_requests', 'is_ignored')) {
            $this->markTestSkipped('is_ignored column required. Run migration.');
        }
    }

    private function requireProjectIdColumn(): void
    {
        $this->requireTables();

        if (!Schema::hasTable('user_projects')) {
            $this->markTestSkipped('user_projects table required.');
        }
        if (!Schema::hasColumn('users_property_requests', 'project_id')) {
            $this->markTestSkipped('project_id column required on users_property_requests. Run migration.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function createProject(User $tenant): Project
    {
        return Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/test.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Test Developer',
            'units' => 10,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
        ]);
    }

    private function createProperty(User $tenant): Property
    {
        return Property::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'sale',
            'property_status' => 'available',
            'area' => 120,
            'completion_status' => 'complete',
            'status' => 1,
            'property_type' => 'residential',
        ]);
    }

    private function createPropertyRequest(int $userId, array $overrides = []): int
    {
        $defaults = [
            'user_id'    => $userId,
            'full_name'  => 'Test Customer',
            'phone'      => '+966501234567',
            'is_active'  => 1,
            'is_read'    => 0,
            'is_ignored' => 0,
            'source'     => 'whatsapp',
            'created_at' => now(),
            'updated_at' => now(),
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
    public function complete_data_updates_city_and_property_type(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'city'          => 'جدة',
            'property_type' => 'residential',
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['request_id', 'has_minimal_data', 'minimal_missing_fields', 'is_complete', 'missing_fields', 'message'],
            ]);

        $this->assertTrue($res->json('data.has_minimal_data'));

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('جدة', $row->city);
        $this->assertEquals('residential', $row->property_type);
    }

    /** @test */
    public function complete_data_maps_purpose_buy_to_sale(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'purpose' => 'buy',
            'city'    => 'الرياض',
        ])->assertOk();

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('sale', $row->purpose);
    }

    /** @test */
    public function complete_data_updates_budget_fields(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'budget_from' => 500000,
            'budget_to'   => 1000000,
            'currency'    => 'SAR',
        ])->assertOk();

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals(500000, (float) $row->budget_from);
        $this->assertEquals(1000000, (float) $row->budget_to);
        $this->assertEquals('SAR', $row->currency);
    }

    /** @test */
    public function complete_data_rejects_invalid_field_values(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id);

        $res = $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'latitude' => 999,  // out of range
        ]);

        $res->assertUnprocessable();
    }

    /** @test */
    public function complete_data_returns_404_for_non_existent_request(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/property_request_99999/complete-data', [
            'city' => 'الرياض',
        ]);

        $res->assertStatus(404);
    }

    /** @test */
    public function complete_data_rejects_non_property_request_ids(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v2/customers-hub/requests/reminder_1/complete-data', [
            'city' => 'الرياض',
        ]);

        $res->assertStatus(404);
    }

    /** @test */
    public function complete_data_only_updates_provided_fields(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $requestId = $this->createPropertyRequest($tenant->id, [
            'city'          => 'الرياض',
            'property_type' => 'residential',
        ]);

        // Only send district, city should not be overwritten
        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'district' => 'حي النزهة',
        ])->assertOk();

        $row = DB::table('users_property_requests')->where('id', $requestId)->first();
        $this->assertEquals('الرياض', $row->city);
        $this->assertEquals('حي النزهة', $row->district);
    }

    /** @test */
    public function complete_data_can_set_project_id(): void
    {
        $this->requireProjectIdColumn();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $requestId = $this->createPropertyRequest($tenant->id);

        $this->postJson("/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data", [
            'project_id' => $project->id,
            'city' => 'الرياض',
        ])->assertOk();

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'project_id' => $project->id,
        ]);
    }

    /** @test */
    public function complete_data_replaces_and_clears_project_and_property_ids(): void
    {
        $this->requireProjectIdColumn();
        if (! Schema::hasTable('property_request_project') || ! Schema::hasTable('user_properties')) {
            $this->markTestSkipped('Multi-link tables required.');
        }

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);
        $projects = [$this->createProject($tenant), $this->createProject($tenant)];
        $properties = [
            $this->createProperty($tenant),
            $this->createProperty($tenant),
        ];
        $requestId = $this->createPropertyRequest($tenant->id);
        $url = "/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data";

        $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['property_request'],
            'search' => '+966501234567',
        ])->assertOk()
            ->assertJsonPath('data.actions.0.project_ids', [])
            ->assertJsonPath('data.actions.0.property_ids', []);

        $this->postJson($url, [
            'project_ids' => array_map(fn ($project) => $project->id, $projects),
            'property_ids' => array_map(fn ($property) => $property->id, $properties),
        ])->assertOk()
            ->assertJsonPath('data.project_ids', [$projects[0]->id, $projects[1]->id])
            ->assertJsonPath('data.property_ids', [$properties[0]->id, $properties[1]->id]);

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'project_id' => $projects[0]->id,
        ]);

        $this->getJson("/api/v2/customers-hub/requests/property_request_{$requestId}")
            ->assertOk()
            ->assertJsonPath('data.action.project_ids', [$projects[0]->id, $projects[1]->id])
            ->assertJsonPath('data.action.property_ids', [$properties[0]->id, $properties[1]->id])
            ->assertJsonPath('data.action.projects.0.id', $projects[0]->id)
            ->assertJsonPath('data.action.projects.1.id', $projects[1]->id)
            ->assertJsonPath('data.action.properties.0.id', $properties[0]->id)
            ->assertJsonPath('data.action.properties.1.id', $properties[1]->id)
            ->assertJsonStructure([
                'data' => ['action' => [
                    'projects' => ['*' => ['id', 'title', 'slug', 'featuredImage']],
                    'properties' => ['*' => ['id', 'title', 'slug', 'featuredImage']],
                ]],
            ]);

        $this->postJson('/api/v2/customers-hub/requests/list', [
            'objectTypes' => ['property_request'],
            'search' => '+966501234567',
        ])->assertOk()
            ->assertJsonPath('data.actions.0.project_ids', [$projects[0]->id, $projects[1]->id])
            ->assertJsonPath('data.actions.0.property_ids', [$properties[0]->id, $properties[1]->id])
            ->assertJsonPath('data.actions.0.projects.0.id', $projects[0]->id)
            ->assertJsonPath('data.actions.0.properties.0.id', $properties[0]->id)
            ->assertJsonStructure([
                'data' => ['actions' => ['*' => [
                    'projects' => ['*' => ['id', 'title', 'slug', 'featuredImage']],
                    'properties' => ['*' => ['id', 'title', 'slug', 'featuredImage']],
                ]]],
            ]);

        $this->postJson($url, ['project_ids' => [], 'property_ids' => []])
            ->assertOk()
            ->assertJsonPath('data.project_ids', [])
            ->assertJsonPath('data.property_ids', []);
        $this->assertDatabaseMissing('property_request_project', ['property_request_id' => $requestId]);
    }

    /** @test */
    public function complete_data_rejects_cross_tenant_and_nonexistent_link_ids_by_field(): void
    {
        $this->requireProjectIdColumn();
        if (! Schema::hasTable('property_request_project') || ! Schema::hasTable('user_properties')) {
            $this->markTestSkipped('Multi-link tables required.');
        }

        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();
        Sanctum::actingAs($tenant);
        $requestId = $this->createPropertyRequest($tenant->id);
        $url = "/api/v2/customers-hub/requests/property_request_{$requestId}/complete-data";
        $foreignProject = $this->createProject($otherTenant);
        $foreignProperty = $this->createProperty($otherTenant);

        $this->postJson($url, ['project_ids' => [$foreignProject->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_ids']);
        $this->postJson($url, ['property_ids' => [$foreignProperty->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_ids']);
        $this->postJson($url, ['project_ids' => [PHP_INT_MAX]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['project_ids']);
        $this->postJson($url, ['property_ids' => [PHP_INT_MAX]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['property_ids']);
    }

    /** @test */
    public function complete_data_requires_authentication(): void
    {
        $res = $this->postJson('/api/v2/customers-hub/requests/property_request_1/complete-data', [
            'city' => 'الرياض',
        ]);
        $res->assertUnauthorized();
    }
}
