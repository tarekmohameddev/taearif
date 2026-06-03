<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\Building;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicBuildingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_archived_buildings_are_not_listed(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $active = Building::create(['user_id' => $tenant->id, 'name' => 'Active Tower', 'is_archived' => false]);
        Building::create(['user_id' => $tenant->id, 'name' => 'Old Tower', 'is_archived' => true]);

        $this->assertNotNull($active->slug);
        $this->assertNotSame('', $active->slug);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/buildings");
        $response->assertOk()->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.slug', $active->slug);

        $this->getJson("/api/v1/tenant-website/{$tenant->username}/buildings/{$active->slug}")
            ->assertOk()
            ->assertJsonPath('building.slug', $active->slug);
    }
}
