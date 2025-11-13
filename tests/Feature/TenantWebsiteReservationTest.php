<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Reservation;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantWebsiteReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_reservation_via_tenant_website_endpoint(): void
    {
        $tenant = User::factory()->create([
            'username' => 'tenant1',
        ]);

        $property = Property::create([
            'user_id' => $tenant->id,
            'status' => 1,
            'type' => 'شقة',
            'purpose' => 'rent',
            'price' => 1000,
        ]);

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test Property',
            'slug' => 'test-slug',
            'address' => 'Riyadh',
        ]);

        $payload = [
            'propertySlug' => 'test-slug',
            'customerName' => 'John Doe',
            'customerPhone' => '+966500000000',
            'desiredDate' => now()->addDay()->toDateString(),
            'message' => 'Please contact me',
        ];

        $res = $this->postJson("/api/v1/tenant-website/tenant1/reservations", $payload);

        $res->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('reservations', [
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'customer_name' => 'John Doe',
            'status' => 'pending',
        ]);
    }
}


