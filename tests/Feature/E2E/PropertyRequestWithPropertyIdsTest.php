<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use App\Models\User\UserCity;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Facades\Schema;

class PropertyRequestWithPropertyIdsTest extends ApiE2ETestCase
{
    private function skipIfMissingProjectIdColumn(): void
    {
        if (! Schema::hasColumn('users_property_requests', 'project_id')) {
            $this->markTestSkipped('project_id column required on users_property_requests. Run migration.');
        }
        if (! Schema::hasTable('user_projects')) {
            $this->markTestSkipped('user_projects table required.');
        }
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

    private function loginAs(User $tenant): string
    {
        $this->fakeRecaptcha();

        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $tenant->email,
            'password' => 'password',
        ]);
        $login->assertOk();

        return (string) $login->json('token');
    }

    /** @test */
    public function create_property_request_without_property_ids_still_works(): void
    {
        $this->fakeRecaptcha();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-without-ids',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $token = $this->loginAs($tenant);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'No Property IDs',
                'phone' => '+966500000001',
                'region' => $city->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'No Property IDs');
    }

    /** @test */
    public function create_property_request_with_valid_property_ids(): void
    {
        $this->fakeRecaptcha();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-with-ids',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $propertyOne = Property::factory()->create([
            'user_id' => $tenant->id,
        ]);
        $propertyTwo = Property::factory()->create([
            'user_id' => $tenant->id,
        ]);

        $token = $this->loginAs($tenant);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'With Property IDs',
                'phone' => '+966500000002',
                'region' => $city->id,
                'property_ids' => [$propertyOne->id, $propertyTwo->id],
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.full_name', 'With Property IDs')
            ->assertJsonPath('data.property_ids', [$propertyOne->id, $propertyTwo->id]);
    }

    /** @test */
    public function create_property_request_with_foreign_property_ids_fails(): void
    {
        $this->fakeRecaptcha();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-foreign-ids',
        ]);

        $otherTenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-foreign-ids-other',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $foreignProperty = Property::factory()->create([
            'user_id' => $otherTenant->id,
        ]);

        $token = $this->loginAs($tenant);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'Foreign Property IDs',
                'phone' => '+966500000003',
                'region' => $city->id,
                'property_ids' => [$foreignProperty->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['property_ids']);
    }

    /** @test */
    public function create_property_request_with_valid_project_id_persists(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-valid-project',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $project = $this->createProject($tenant);
        $token = $this->loginAs($tenant);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'With Project ID',
                'phone' => '+966500000011',
                'region' => $city->id,
                'project_id' => $project->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.project_id', $project->id);

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $response->json('data.id'),
            'user_id' => $tenant->id,
            'project_id' => $project->id,
        ]);
    }

    /** @test */
    public function create_property_request_with_foreign_project_id_fails(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-foreign-project',
        ]);

        $otherTenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-foreign-project-other',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $foreignProject = $this->createProject($otherTenant);
        $token = $this->loginAs($tenant);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'Foreign Project ID',
                'phone' => '+966500000012',
                'region' => $city->id,
                'project_id' => $foreignProject->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['project_id']);
    }

    /** @test */
    public function create_property_request_normalizes_empty_string_project_id_to_null(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-empty-project',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $token = $this->loginAs($tenant);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'Empty Project ID',
                'phone' => '+966500000013',
                'region' => $city->id,
                'project_id' => '',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $response->json('data.id'),
            'user_id' => $tenant->id,
            'project_id' => null,
        ]);
    }

    /** @test */
    public function update_property_request_can_set_and_clear_project_id(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-pr-update-project',
        ]);

        $city = UserCity::first();
        $this->assertNotNull($city, 'Test DB should have at least one user_cities row');

        $project = $this->createProject($tenant);
        $token = $this->loginAs($tenant);

        $create = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/property-requests', [
                'full_name' => 'Update Project ID',
                'phone' => '+966500000014',
                'region' => $city->id,
            ]);
        $create->assertStatus(201);
        $requestId = (int) $create->json('data.id');

        $set = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/property-requests/' . $requestId, [
                'project_id' => $project->id,
            ]);
        $set->assertOk()
            ->assertJsonPath('data.project_id', $project->id);

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'project_id' => $project->id,
        ]);

        $clear = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/property-requests/' . $requestId, [
                'project_id' => null,
            ]);
        $clear->assertOk();

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'project_id' => null,
        ]);

        $emptyString = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/property-requests/' . $requestId, [
                'project_id' => $project->id,
            ]);
        $emptyString->assertOk();

        $normalize = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson('/api/v1/property-requests/' . $requestId, [
                'project_id' => '',
            ]);
        $normalize->assertOk();

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'project_id' => null,
        ]);
    }
}

