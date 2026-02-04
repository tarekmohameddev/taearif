<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;
use App\Models\Membership;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;

/**
 * E2E: Dashboard access requires active package/subscription.
 * 
 * Tests the require.active.package middleware on dashboard endpoints.
 * - With inactive/expired package → 402
 * - With active package → 200
 */
class DashboardRequiresActivePackageTest extends ApiE2ETestCase
{
    /**
     * Create a tenant user and login to get a Bearer token.
     *
     * @return array{token: string, user: User}
     */
    private function createTenantAndLogin(): array
    {
        try {
            $this->fakeRecaptcha();

            $user = User::factory()->create([
                'account_type' => 'tenant',
                'email' => 'e2e-dashboard-tenant@example.com',
                'password' => Hash::make('password'),
                'active' => true,
                'status' => 1,
            ]);

            $loginResponse = $this->postJson('/api/login', [
                'recaptcha_token' => 'fake',
                'email' => $user->email,
                'password' => 'password',
            ]);

            $loginResponse->assertOk();
            $token = $loginResponse->json('token');

            return ['token' => $token, 'user' => $user];
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false) {
                $this->markTestSkipped('users table or schema missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }
    }

    /**
     * Create or update a membership record for the user.
     *
     * @param int $userId
     * @param bool $active true = active package, false = inactive
     * @return Membership
     */
    private function setMembershipState(int $userId, bool $active): Membership
    {
        try {
            $membership = Membership::firstOrNew(['user_id' => $userId]);

            if ($active) {
                $membership->status = 1;
                $membership->start_date = now()->subDays(10);
                $membership->expire_date = now()->addDays(30);
            } else {
                $membership->status = 0;
                $membership->start_date = now()->subDays(40);
                $membership->expire_date = now()->subDays(10); // expired
            }

            $membership->package_id = 1; // Assume some package exists or nullable
            $membership->price = 0;
            $membership->currency = 'USD';
            $membership->currency_symbol = '$';
            $membership->payment_method = 'test';
            $membership->transaction_id = 'e2e-test-' . uniqid();
            $membership->save();

            // Clear the membership cache to ensure middleware sees the updated state
            \App\Services\MembershipCacheService::clearCache($userId);

            return $membership;
        } catch (QueryException $e) {
            $msg = $e->getMessage();
            if (strpos($msg, "doesn't exist") !== false || strpos($msg, 'Base table') !== false) {
                $this->markTestSkipped('memberships table missing. Restore taearif_testing from dump.');
            }
            throw $e;
        }
    }

    /** @test */
    public function dashboard_endpoint_requires_active_package(): void
    {
        // Step 1: Create tenant and get auth token
        ['token' => $token, 'user' => $user] = $this->createTenantAndLogin();

        // Step 2: Set membership to inactive/expired
        $this->setMembershipState($user->id, false);

        // Step 3: Call dashboard endpoint with inactive package → expect 402
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/dashboard');

        $response->assertStatus(402)
            ->assertJson(['message' => 'No active package.']);

        // Step 4: Activate the package (same membership, updated)
        $this->setMembershipState($user->id, true);

        // Step 5: Call the same endpoint with active package → expect 200
        $responseActive = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/dashboard');

        $responseActive->assertOk();
        // Minimal assertion: just check that we got a successful response with some data structure
        $this->assertTrue(
            $responseActive->json('status') === true || 
            $responseActive->json('status') === 'success' ||
            is_array($responseActive->json('data')),
            'Dashboard response should have status true/success or data array'
        );
    }
}
