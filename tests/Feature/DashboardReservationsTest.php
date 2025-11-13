<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Reservation;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function seedReservation(User $tenant): Reservation
    {
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

        return Reservation::create([
            'tenant_id' => $tenant->id,
            'property_id' => $property->id,
            'type' => 'rent',
            'status' => 'pending',
            'customer_name' => 'John Doe',
            'customer_phone' => '+966500000000',
            'desired_date' => now()->addDay()->toDateString(),
            'notes' => 'Test',
            'metadata' => [],
        ]);
    }

    public function test_list_reservations(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $this->seedReservation($tenant);

        $res = $this->getJson('/api/v1/reservations');
        $res->assertOk()->assertJson(['success' => true]);
    }

    public function test_accept_and_reject_reservation(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $reservation = $this->seedReservation($tenant);

        $accept = $this->postJson("/api/v1/reservations/{$reservation->id}/accept", [
            'confirmPayment' => true,
            'notes' => 'Ok',
        ]);
        $accept->assertOk()->assertJson(['success' => true]);

        $reject = $this->postJson("/api/v1/reservations/{$reservation->id}/reject", [
            'reason' => 'Not suitable',
        ]);
        $reject->assertOk()->assertJson(['success' => true]);
    }

    public function test_stats_endpoint(): void
    {
        $tenant = User::factory()->create();
        Sanctum::actingAs($tenant);
        $this->seedReservation($tenant);

        $res = $this->getJson('/api/v1/reservations/stats');
        $res->assertOk()->assertJson(['success' => true]);
    }
}


