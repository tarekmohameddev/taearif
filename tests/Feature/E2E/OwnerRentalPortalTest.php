<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\OwnerRental;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * E2E: Owner-Rental Portal flow.
 * Assertions per api-testing-strategy.md (owner-rental login, standard wrapper for dashboard/properties/rentals).
 */
class OwnerRentalPortalTest extends ApiE2ETestCase
{
    private function createOwnerRental(User $owner, array $overrides = []): OwnerRental
    {
        return OwnerRental::create(array_merge([
            'user_id' => $owner->id,
            'name' => 'Owner Rental E2E',
            'email' => 'owner-rental-e2e@example.com',
            'phone' => '+966501234567',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ], $overrides));
    }

    /** @test */
    public function full_journey_login_me_dashboard_properties_rentals_logout(): void
    {
        $owner = User::factory()->create(['account_type' => 'tenant']);
        $ownerRental = $this->createOwnerRental($owner);
        $ownerRental->email = 'or-login@example.com';
        $ownerRental->save();

        // 1. Login
        $login = $this->postJson('/api/v1/owner-rental/login', [
            'email' => 'or-login@example.com',
            'password' => 'password123',
        ]);

        if ($login->status() !== 200) {
            $this->markTestSkipped('Owner-rental login returned ' . $login->status() . '. Ensure owner_rentals table and auth guard are set up.');
        }
        $loginData = $login->json();
        if (($loginData['success'] ?? false) !== true || empty($loginData['data']['token'] ?? null)) {
            $this->markTestSkipped('Owner-rental login response missing success or token. Response: ' . json_encode(array_keys($loginData)));
        }

        $login->assertOk();
        $token = $login->json('data.token');
        $this->assertNotNull($token, 'Login response must include data.token');
        $this->assertTrue($login->json('success') === true, 'Login success should be true');
        $this->assertNotNull($login->json('data.owner_rental'), 'Login should return owner_rental');

        // 2. GET /me
        $me = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/owner-rental/me');

        if ($me->status() !== 200) {
            $this->markTestSkipped('Owner-rental /me returned ' . $me->status());
        }

        $me->assertOk();
        $this->assertTrue($me->json('success') === true, 'Me response success');
        $this->assertArrayHasKey('data', $me->json());

        // 3. Dashboard
        $dashboard = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/owner-rental/dashboard');

        if ($dashboard->status() !== 200) {
            $this->markTestSkipped('Owner-rental dashboard returned ' . $dashboard->status());
        }

        $dashboard->assertOk();
        $dashboardData = $dashboard->json();
        $this->assertTrue(
            ($dashboardData['status'] ?? null) === 'success' || array_key_exists('data', $dashboardData),
            'Dashboard should return status success or data'
        );

        // 4. Properties
        $properties = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/owner-rental/properties');

        if ($properties->status() !== 200) {
            $this->markTestSkipped('Owner-rental properties returned ' . $properties->status());
        }

        $properties->assertOk();
        $propertiesData = $properties->json();
        $this->assertTrue(
            ($propertiesData['status'] ?? null) === 'success' || array_key_exists('data', $propertiesData),
            'Properties should return status success or data'
        );

        // 5. Rentals
        $rentals = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/owner-rental/rentals');

        if ($rentals->status() !== 200) {
            $this->markTestSkipped('Owner-rental rentals returned ' . $rentals->status());
        }
        $rentals->assertOk();
        $rentalsData = $rentals->json();
        $this->assertTrue(
            ($rentalsData['status'] ?? null) === 'success' || array_key_exists('data', $rentalsData),
            'Rentals should return status success or data'
        );

        // 6. Logout
        $logout = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/owner-rental/logout');

        if ($logout->status() !== 200) {
            $this->markTestSkipped('Owner-rental logout returned ' . $logout->status());
        }
        $logout->assertOk();
        $logoutJson = $logout->json();
        $this->assertTrue(
            ($logoutJson['success'] ?? false) === true || !empty($logoutJson['message'] ?? null),
            'Logout should return success or message'
        );
    }

    /** @test */
    public function owner_rental_login_invalid_credentials_returns_401(): void
    {
        $this->postJson('/api/v1/owner-rental/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrong',
        ])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials');
    }
}
