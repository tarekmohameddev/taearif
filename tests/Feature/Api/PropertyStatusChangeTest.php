<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyStatusChangeTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    public function test_reserved_status_requires_customer_id(): void
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Permission::firstOrCreate(['name' => 'properties.change_status', 'guard_name' => 'sanctum']);
        $user->givePermissionTo('properties.change_status');
        Sanctum::actingAs($user);

        $property = Property::create([
            'user_id' => $user->id,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'reserved',
        ])->assertStatus(422);
    }

    public function test_status_change_updates_unit_status(): void
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Permission::firstOrCreate(['name' => 'properties.change_status', 'guard_name' => 'sanctum']);
        $user->givePermissionTo('properties.change_status');
        Sanctum::actingAs($user);

        $property = Property::create([
            'user_id' => $user->id,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ]);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk()
            ->assertJsonPath('data.unit_status', 'sold');
    }
}
