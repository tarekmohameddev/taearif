<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\User;

/**
 * E2E: Credits flow (packages public, balance auth).
 * GET packages (public) → login → GET balance.
 */
class CreditsPackagesTest extends ApiE2ETestCase
{
    /** @test */
    public function packages_public_then_balance_after_login(): void
    {
        $packages = $this->getJson('/api/v1/credits/packages');
        $this->assertTrue(
            in_array($packages->status(), [200, 400], true),
            'Packages endpoint should return 200 or 400. Got: ' . $packages->status()
        );
        if ($packages->status() === 200) {
            $status = $packages->json('status');
            $this->assertTrue($status === true || $status === 'success', 'Packages response status should be true or success');
            $this->assertArrayHasKey('status', $packages->json());
            $data = $packages->json('data');
            if ($data !== null) {
                $this->assertIsArray($data);
            }
        }

        // Balance requires auth; only run when we have a full schema (users table + register/login work)
        $this->fakeRecaptcha();
        $user = User::factory()->create(['account_type' => 'tenant']);
        $login = $this->postJson('/api/login', [
            'recaptcha_token' => 'fake',
            'email' => $user->email,
            'password' => 'password',
        ]);
        $login->assertOk();
        $token = $login->json('token');

        $balance = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/credits/balance');
        $balance->assertOk();
        $balanceStatus = $balance->json('status');
        $this->assertTrue($balanceStatus === true || $balanceStatus === 'success', 'Balance response status should be true or success');
        $this->assertArrayHasKey('data', $balance->json());
    }
}
