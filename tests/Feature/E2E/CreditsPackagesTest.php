<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\Api\marketing\CreditPackage;
use App\Models\User;

/**
 * E2E: Credits flow (packages public, balance auth).
 * GET packages (public) → login → GET balance.
 */
class CreditsPackagesTest extends ApiE2ETestCase
{
    /** @test */
    public function packages_include_raw_bilingual_names_and_honour_locale(): void
    {
        $package = CreditPackage::factory()->create([
            'name' => 'Starter Credits EN',
            'name_ar' => 'باقة البداية',
            'description' => 'English starter description',
            'description_ar' => 'وصف الباقة بالعربية',
            'is_active' => true,
        ]);

        $default = $this->getJson('/api/v1/credits/packages');
        $default->assertOk();
        $defaultPayload = $this->findPackagePayload($default->json('data'), $package->id);

        $this->assertSame($package->name_ar, $defaultPayload['name_ar']);
        $this->assertSame($package->name, $defaultPayload['name_en']);
        $this->assertSame($package->description_ar, $defaultPayload['description_ar']);
        $this->assertSame($package->description, $defaultPayload['description_en']);
        $this->assertSame($package->name, $defaultPayload['name']);
        $this->assertSame($package->description, $defaultPayload['description']);

        $arabic = $this->getJson('/api/v1/credits/packages?locale=ar');
        $arabic->assertOk();
        $arabicPayload = $this->findPackagePayload($arabic->json('data'), $package->id);

        $this->assertSame($package->name_ar, $arabicPayload['name_ar']);
        $this->assertSame($package->name, $arabicPayload['name_en']);
        $this->assertSame($package->description_ar, $arabicPayload['description_ar']);
        $this->assertSame($package->description, $arabicPayload['description_en']);
        $this->assertSame($package->name_ar, $arabicPayload['name']);
        $this->assertSame($package->description_ar, $arabicPayload['description']);
    }

    /**
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function findPackagePayload($data, int $packageId): array
    {
        $this->assertIsArray($data);

        $payload = collect($data)->first(function ($item) use ($packageId) {
            return is_array($item) && (int) ($item['id'] ?? 0) === $packageId;
        });

        $this->assertIsArray($payload, 'Expected the created credit package in the public packages payload.');

        return $payload;
    }

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
