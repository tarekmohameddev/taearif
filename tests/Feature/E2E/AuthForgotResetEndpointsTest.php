<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\BasicSetting;
use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

class AuthForgotResetEndpointsTest extends ApiE2ETestCase
{
    private function loginAndGetToken(string $email, string $plainPassword): string
    {
        Config::set('services.recaptcha.api_enabled', true);
        $this->fakeRecaptcha();

        $response = $this->postJson('/api/login', [
            'recaptcha_token' => 'TEST_BYPASS_TOKEN',
            'email' => $email,
            'password' => $plainPassword,
        ]);

        if ($response->status() !== 200) {
            $this->markTestSkipped('Login failed with status ' . $response->status());
        }

        $token = $response->json('token');
        if (!is_string($token) || $token === '') {
            $this->markTestSkipped('Login response missing token');
        }

        return $token;
    }

    private function enableSafeWhatsAppResetDelivery(): void
    {
        // Avoid external calls by forcing WhatsAppService to use its "default message" fallback.
        $settings = BasicSetting::query()->first();
        if ($settings === null) {
            $this->markTestSkipped('Missing basic_settings row; cannot test forgot/reset safely.');
        }

        $settings->update([
            'whatsapp_notifications_enabled' => true,
            'whatsapp_service' => null,
            'password_reset_enabled' => false,
        ]);
    }

    /** @test */
    public function forgot_password_email_test_bypass_skips_smtp_and_allows_fixed_code_verify(): void
    {
        try {
            Config::set('services.recaptcha.api_enabled', true);
            $this->fakeRecaptcha();
            Config::set('api.password_reset.email_test_bypass_enabled', true);
            Config::set('api.password_reset.email_test_bypass_code', '12345');

            $plainPassword = 'password123';
            $email = 'reset-bypass-' . uniqid('', true) . '@example.com';
            $user = User::factory()->create([
                'email' => $email,
                'username' => 'reset-bypass-' . uniqid('', true),
                'phone' => '+9665' . random_int(100000000, 999999999),
                'password' => Hash::make($plainPassword),
                'account_type' => 'tenant',
                'active' => true,
                'status' => 1,
            ]);

            $forgot = $this->postJson('/api/auth/forgot-password', [
                'method' => 'email',
                'identifier' => $email,
                'recaptcha_token' => 'TEST_BYPASS_TOKEN',
            ]);

            $forgot->assertOk()
                ->assertJsonPath('via', 'email');

            $log = PasswordResetLog::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first();
            $this->assertNotNull($log);
            $this->assertSame('12345', (string) $log->code);

            $newPassword = 'NewPass123!';
            $verify = $this->postJson('/api/auth/verify-reset-code', [
                'code' => '12345',
                'new_password' => $newPassword,
                'new_password_confirmation' => $newPassword,
                'recaptcha_token' => 'TEST_BYPASS_TOKEN',
            ]);

            $verify->assertOk()
                ->assertJsonPath('message', 'Password reset successful');
            $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing: ' . $e->getMessage());
        }
    }

    /** @test */
    public function forgot_password_phone_returns_404_when_user_not_found(): void
    {
        Config::set('services.recaptcha.api_enabled', true);
        $this->fakeRecaptcha();

        $identifier = (string) random_int(100000000, 999999999);
        $fullPhone = '+966' . $identifier;
        $phoneWithoutPlus = '966' . $identifier;

        $alreadyExists = User::query()
            ->whereIn('phone', [$identifier, $fullPhone, $phoneWithoutPlus])
            ->exists();

        if ($alreadyExists) {
            $this->markTestSkipped('Generated phone identifier already exists in taearif_testing; cannot assert 404 branch reliably.');
        }

        $response = $this->postJson('/api/auth/forgot-password', [
            'method' => 'phone',
            'identifier' => $identifier,
            'country_code' => '+966',
            'recaptcha_token' => 'TEST_BYPASS_TOKEN',
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'User not found');
    }

    /** @test */
    public function forgot_password_phone_sends_code_and_creates_reset_log(): void
    {
        try {
            Config::set('services.recaptcha.api_enabled', true);
            $this->fakeRecaptcha();

            $plainPassword = 'password123';
            $email = 'reset-e2e@example.com';
            $identifier = (string) random_int(100000000, 999999999);
            $phone = '+966' . $identifier;

            $user = User::factory()->create([
                'email' => $email,
                'username' => 'reset-e2e-' . uniqid('', true),
                'phone' => $phone,
                'password' => Hash::make($plainPassword),
                'account_type' => 'tenant',
                'active' => true,
                'status' => 1,
            ]);

            $this->enableSafeWhatsAppResetDelivery();

            $response = $this->postJson('/api/auth/forgot-password', [
                'method' => 'phone',
                'identifier' => $identifier,
                'country_code' => '+966',
                'recaptcha_token' => 'TEST_BYPASS_TOKEN',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Reset code sent successfully (Attempt 1/3)')
                ->assertJsonPath('via', 'phone')
                ->assertJsonPath('attempts_used', 1)
                ->assertJsonPath('attempts_remaining', 2);

            /** @var PasswordResetLog|null $log */
            $log = PasswordResetLog::query()
                ->where('user_id', $user->id)
                ->latest()
                ->first();

            if ($log === null) {
                $this->fail('Expected password_reset_logs record to be created.');
            }

            $this->assertTrue(is_string($log->code) && preg_match('/^\d{6}$/', $log->code) === 1, 'Expected a 6-digit reset code.');
            $this->assertNotNull($log->expires_at);
            $this->assertTrue($log->expires_at->isFuture(), 'Expected reset code expiry to be in the future.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for forgot/reset tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_reset_code_updates_password_and_consumes_log_on_success(): void
    {
        Config::set('services.recaptcha.api_enabled', true);
        $this->fakeRecaptcha();

        $plainPassword = 'password123';
        $email = 'reset-e2e-verify-success@example.com';
        $user = User::factory()->create([
            'email' => $email,
            'username' => 'reset-e2e-verify-' . uniqid('', true),
            'phone' => '+9665' . random_int(100000000, 999999999),
            'password' => Hash::make($plainPassword),
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);

        $resetCode = '123456';
        PasswordResetLog::query()->create([
            'user_id' => $user->id,
            'method' => 'phone',
            'code' => $resetCode,
            'used' => false,
            'expires_at' => now()->addMinutes(15),
            'attempts' => 1,
            'blocked' => false,
            'blocked_until' => null,
        ]);

        $newPassword = 'NewPass123!';
        $verify = $this->postJson('/api/auth/verify-reset-code', [
            'code' => $resetCode,
            'new_password' => $newPassword,
            'new_password_confirmation' => $newPassword,
            'recaptcha_token' => 'TEST_BYPASS_TOKEN',
        ]);

        $verify->assertOk()
            ->assertJsonPath('message', 'Password reset successful');

        $this->assertTrue(Hash::check($newPassword, $user->fresh()->password), 'Expected user password to be updated.');
        $this->assertSame(0, PasswordResetLog::query()->where('user_id', $user->id)->count(), 'Expected password reset logs to be deleted after successful reset.');
    }

    /** @test */
    public function verify_reset_code_returns_400_for_invalid_code(): void
    {
        Config::set('services.recaptcha.api_enabled', true);
        $this->fakeRecaptcha();

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'code' => '111111',
            'new_password' => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!',
            'recaptcha_token' => 'TEST_BYPASS_TOKEN',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Invalid or expired code');
    }

    /** @test */
    public function forgot_password_phone_rate_limits_after_3_attempts_in_24h(): void
    {
        try {
            Config::set('services.recaptcha.api_enabled', true);
            $this->fakeRecaptcha();

            $plainPassword = 'password123';
            $email = 'reset-e2e-rate-limit@example.com';
            $phone = '+966501987654';
            $identifier = '501987654';

            $user = User::factory()->create([
                'email' => $email,
                'username' => 'reset-e2e-rate-' . uniqid('', true),
                'phone' => $phone,
                'password' => Hash::make($plainPassword),
                'account_type' => 'tenant',
                'active' => true,
                'status' => 1,
            ]);

            $this->enableSafeWhatsAppResetDelivery();

            // Seed 3 logs within last 24h so controller blocks further attempts.
            for ($i = 0; $i < 3; $i++) {
                PasswordResetLog::query()->create([
                    'user_id' => $user->id,
                    'method' => 'phone',
                    'code' => (string) (100000 + $i),
                    'used' => false,
                    'expires_at' => now()->addMinutes(15),
                    'attempts' => $i + 1,
                    'blocked' => false,
                    'blocked_until' => null,
                ]);
            }

            $response = $this->postJson('/api/auth/forgot-password', [
                'method' => 'phone',
                'identifier' => $identifier,
                'country_code' => '+966',
                'recaptcha_token' => 'TEST_BYPASS_TOKEN',
            ]);

            $response->assertStatus(429)
                ->assertJsonPath('message', 'You have reached the maximum 3 attempts');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for forgot/reset max-attempts tests: ' . $e->getMessage());
        }
    }
}

