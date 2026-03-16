<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use App\Models\User\UserCity;
use App\Models\User\RealestateManagement\Property;

class PropertyRequestWithPropertyIdsTest extends ApiE2ETestCase
{
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

        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $tenant->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');

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

        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $tenant->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');

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

        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $tenant->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');

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
}

