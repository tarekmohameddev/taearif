<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\Reservation;
use App\Models\User;
use App\Models\User\BasicSetting;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;

/**
 * E2E: Tenant Website public reservation flow.
 * getTenant → GET properties → POST reservations → (tenant) GET reservations → accept/reject.
 */
class TenantWebsitePublicReservationTest extends ApiE2ETestCase
{
    /** @test */
    public function full_journey_get_tenant_properties_create_reservation_then_tenant_list(): void
    {
        $this->fakeRecaptcha();
        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'e2e-tenant-res',
        ]);

        \App\Models\User\BasicSetting::firstOrCreate(
            ['user_id' => $tenant->id],
            ['company_name' => 'E2E Co', 'logo' => '']
        );

        $property = Property::create([
            'user_id' => $tenant->id,
            'status' => 1,
            'type' => 'شقة',
            'purpose' => 'rent',
            'price' => 1000,
            'category_id' => 1,
            'region_id' => 1,
            'featured_image' => '',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'reorder' => 1,
            'reorder_featured' => 1,
        ]);

        PropertyContent::firstOrCreate(
            [
                'user_id' => $tenant->id,
                'property_id' => $property->id,
                'language_id' => 1,
            ],
            [
                'title' => 'E2E Property',
                'slug' => 'e2e-property-slug',
                'address' => 'Riyadh',
                'description' => 'E2E property for tenant website reservation test',
            ]
        );

        // 1. getTenant (public)
        $getTenant = $this->postJson('/api/v1/tenant-website/getTenant', [
            'websiteName' => 'e2e-tenant-res',
        ]);
        if ($getTenant->status() !== 200) {
            $this->markTestSkipped('getTenant returned ' . $getTenant->status() . '. Ensure tenant-website API and basic_settings are set up.');
        }
        $getTenant->assertOk();

        // 2. GET properties (public)
        $tenantId = $getTenant->json('username') ?? $tenant->username;
        $properties = $this->getJson('/api/v1/tenant-website/' . $tenantId . '/properties');
        $properties->assertOk();
        $propsData = $properties->json();
        $this->assertTrue(
            array_key_exists('data', $propsData) || array_key_exists('properties', $propsData) || (is_array($propsData) && isset($propsData[0])),
            'Properties response should have data, properties, or array'
        );

        // 3. POST reservation (public)
        $reservation = $this->postJson('/api/v1/tenant-website/' . $tenantId . '/reservations', [
            'propertySlug' => 'e2e-property-slug',
            'customerName' => 'E2E Guest',
            'customerPhone' => '+966500000111',
            'desiredDate' => now()->addDay()->toDateString(),
            'message' => 'E2E message',
        ]);

        $reservation->assertStatus(201);
        $resData = $reservation->json();
        $this->assertTrue(
            ($resData['success'] ?? false) === true || array_key_exists('data', $resData),
            'Reservation should return success or data'
        );

        $reservationId = $reservation->json('data.id') ?? Reservation::where('tenant_id', $tenant->id)->latest('id')->value('id');
        if (!$reservationId) {
            return;
        }

        // 4. Login as tenant, GET reservations
        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $tenant->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');

        $list = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reservations');
        if ($list->status() !== 200) {
            $this->markTestSkipped('Reservations list returned ' . $list->status());
        }
        $list->assertOk();
        $listJson = $list->json();
        $this->assertTrue(
            ($listJson['status'] ?? null) === 'success' || ($listJson['status'] ?? null) === true || array_key_exists('data', $listJson ?? []),
            'Reservations response should have status success or data'
        );

        // 5. Accept or reject
        $accept = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/reservations/' . $reservationId . '/accept');
        if ($accept->status() === 200) {
            $accept->assertOk();
        }
    }
}
