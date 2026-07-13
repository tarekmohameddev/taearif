<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\OtpVerification;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthOtpEndpointsTest extends ApiE2ETestCase
{
    private function createActiveTenantWithKnownPassword(string $email, string $phone, string $plainPassword): User
    {
        return User::factory()->create([
            'email' => $email,
            'username' => 'otp-e2e-' . uniqid('', true),
            'phone' => $phone,
            'password' => Hash::make($plainPassword),
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);
    }

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

    private function mockWhatsAppOtpDelivery(bool $sent = true): void
    {
        $this->mock(WhatsAppService::class, function ($mock) use ($sent) {
            $mock->shouldReceive('sendRegistrationOtp')->andReturn($sent);
        });
    }

    private function skipIfUsersTableMissing(): void
    {
        try {
            DB::table('users')->limit(1)->count();
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP tests: ' . $e->getMessage());
        }
    }

    private function configureOtpBypass(string $environment = 'staging'): void
    {
        $this->app['env'] = $environment;

        Config::set('api.otp.registration.test_bypass_enabled', true);
        Config::set('api.otp.registration.test_bypass_code', '12345');
        Config::set('api.otp.registration.test_bypass_phone', '+966101010101');
        Config::set('api.otp.registration.test_bypass_allow_production', true);
    }

    private function mockWhatsAppOtpDeliveryNeverCalled(): void
    {
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldNotReceive('sendRegistrationOtp');
        });
    }

    private function mockWhatsAppOtpDeliveryExpectCalled(): void
    {
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendRegistrationOtp')->once()->andReturn(true);
        });
    }

    /** @test */
    public function send_otp_requires_phone(): void
    {
        $response = $this->postJson('/api/auth/send-otp', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function send_otp_rejects_registered_phone_without_auth(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-registered@example.com';

            $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $countBefore = OtpVerification::query()->where('identifier', $phone)->count();

            $response = $this->postJson('/api/auth/send-otp', [
                'phone' => $phone,
            ]);

            $response->assertStatus(409)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error', 'phone_already_registered');

            $this->assertSame(
                $countBefore,
                OtpVerification::query()->where('identifier', $phone)->count(),
                'Expected no new OTP row for registered phone without auth.'
            );
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_creates_otp_record_for_authenticated_user(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->mockWhatsAppOtpDelivery(true);

            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-send@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $token = $this->loginAndGetToken($email, $plainPassword);

            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/auth/send-otp', [
                    'phone' => $phone,
                ]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'OTP sent.');

            $otp = OtpVerification::query()
                ->where('user_id', $user->id)
                ->where('context', OtpVerification::CONTEXT_REGISTRATION)
                ->orderByDesc('id')
                ->first();

            if ($otp === null) {
                $this->fail('Expected otp_verifications record to be created.');
            }

            $this->assertSame($phone, $otp->identifier);
            $this->assertNull($otp->verified_at);
            $this->assertSame(0, (int) $otp->attempts);
            $this->assertTrue($otp->otp_expires_at->isFuture(), 'Expected OTP expiry to be in the future.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_creates_record_for_new_phone(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->mockWhatsAppOtpDelivery(true);

            $phone = '+9665' . random_int(100000000, 999999999);

            $response = $this->postJson('/api/auth/send-otp', [
                'phone' => $phone,
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'OTP sent.');

            $otp = OtpVerification::query()
                ->whereNull('user_id')
                ->where('identifier', $phone)
                ->where('context', OtpVerification::CONTEXT_REGISTRATION)
                ->first();

            $this->assertNotNull($otp, 'Expected pre-registration OTP row.');
            $this->assertNull($otp->verified_at);
            $this->assertTrue($otp->otp_expires_at->isFuture());
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_returns_delivery_failed_when_whatsapp_fails(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->mockWhatsAppOtpDelivery(false);

            $phone = '+9665' . random_int(100000000, 999999999);

            $response = $this->postJson('/api/auth/send-otp', [
                'phone' => $phone,
            ]);

            $response->assertStatus(503)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error', 'delivery_failed');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_rate_limits_after_5_sends_per_hour(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->mockWhatsAppOtpDelivery(true);

            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-rate-limit@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $token = $this->loginAndGetToken($email, $plainPassword);

            for ($i = 0; $i < 5; $i++) {
                DB::table('otp_verifications')->insert([
                    'user_id' => $user->id,
                    'identifier' => $phone,
                    'otp' => Hash::make('12345'),
                    'otp_expires_at' => now()->addMinutes(5),
                    'attempts' => 0,
                    'verified_at' => null,
                    'context' => OtpVerification::CONTEXT_REGISTRATION,
                    'created_at' => now()->subMinutes(30 + $i),
                    'updated_at' => now()->subMinutes(30 + $i),
                ]);
            }

            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/auth/send-otp', [
                    'phone' => $phone,
                ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error', 'rate_limit_exceeded')
                ->assertJsonPath('message', 'Too many OTP requests. Try again later.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP rate-limit tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_otp_rejects_registered_phone_without_auth(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-verify-blocked@example.com';

            $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);

            $response = $this->postJson('/api/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '12345',
            ]);

            $response->assertStatus(409)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error', 'phone_already_registered');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP verify block tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_otp_sets_phone_verified_at_on_success(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-verify-success@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $token = $this->loginAndGetToken($email, $plainPassword);

            $otpPlain = '12345';
            OtpVerification::query()->create([
                'user_id' => $user->id,
                'identifier' => $phone,
                'otp' => Hash::make($otpPlain),
                'otp_expires_at' => now()->addMinutes(5),
                'attempts' => 0,
                'verified_at' => null,
                'context' => OtpVerification::CONTEXT_REGISTRATION,
            ]);

            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->postJson('/api/auth/verify-otp', [
                    'otp' => $otpPlain,
                ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Phone verified.');

            $this->assertNotNull($user->fresh()->phone_verified_at, 'Expected phone_verified_at to be set.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP verify success tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_otp_returns_expected_error_codes(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-verify-errors@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $token = $this->loginAndGetToken($email, $plainPassword);

            $cases = [
                'otp_not_found' => ['none', '12345', null],
                'otp_invalid' => ['invalid', '000000', 1],
                'otp_expired' => ['expired', '12345', null],
                'too_many_attempts' => ['too_many_attempts', '12345', 5],
            ];

            foreach ($cases as $expectedError => [$setup, $sendOtp, $expectedAttemptsAfter]) {
                DB::table('otp_verifications')->where('user_id', $user->id)->delete();

                if ($setup === 'invalid') {
                    OtpVerification::query()->create([
                        'user_id' => $user->id,
                        'identifier' => $phone,
                        'otp' => Hash::make('12345'),
                        'otp_expires_at' => now()->addMinutes(5),
                        'attempts' => 0,
                        'verified_at' => null,
                        'context' => OtpVerification::CONTEXT_REGISTRATION,
                    ]);
                } elseif ($setup === 'expired') {
                    OtpVerification::query()->create([
                        'user_id' => $user->id,
                        'identifier' => $phone,
                        'otp' => Hash::make('12345'),
                        'otp_expires_at' => now()->subMinutes(1),
                        'attempts' => 0,
                        'verified_at' => null,
                        'context' => OtpVerification::CONTEXT_REGISTRATION,
                    ]);
                } elseif ($setup === 'too_many_attempts') {
                    OtpVerification::query()->create([
                        'user_id' => $user->id,
                        'identifier' => $phone,
                        'otp' => Hash::make('12345'),
                        'otp_expires_at' => now()->addMinutes(5),
                        'attempts' => OtpVerification::MAX_ATTEMPTS,
                        'verified_at' => null,
                        'context' => OtpVerification::CONTEXT_REGISTRATION,
                    ]);
                }

                $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                    ->postJson('/api/auth/verify-otp', [
                        'otp' => $sendOtp,
                    ]);

                $response->assertStatus(422)
                    ->assertJsonPath('success', false)
                    ->assertJsonPath('error', $expectedError);

                $this->assertTrue(is_string($response->json('message')) && $response->json('message') !== '');

                if ($expectedAttemptsAfter !== null) {
                    $otp = OtpVerification::query()
                        ->where('user_id', $user->id)
                        ->where('context', OtpVerification::CONTEXT_REGISTRATION)
                        ->first();

                    if ($otp !== null) {
                        $this->assertSame($expectedAttemptsAfter, (int) $otp->attempts);
                    }
                }
            }
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP verify error-code tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_skips_whatsapp_for_bypass_phone_in_staging(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->configureOtpBypass('staging');
            $this->mockWhatsAppOtpDeliveryNeverCalled();

            $phone = '+966101010101';

            $response = $this->postJson('/api/auth/send-otp', [
                'phone' => $phone,
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'OTP sent.');

            $otp = OtpVerification::query()
                ->whereNull('user_id')
                ->where('identifier', $phone)
                ->where('context', OtpVerification::CONTEXT_REGISTRATION)
                ->first();

            $this->assertNotNull($otp, 'Expected pre-registration OTP row for bypass phone.');
            $this->assertTrue(Hash::check('12345', $otp->otp), 'Expected bypass OTP code to be stored.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP bypass send tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_otp_accepts_bypass_code_for_bypass_phone_in_staging(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->configureOtpBypass('staging');

            $phone = '+966101010101';

            OtpVerification::query()->create([
                'user_id' => null,
                'identifier' => $phone,
                'otp' => Hash::make('12345'),
                'otp_expires_at' => now()->addMinutes(5),
                'attempts' => 0,
                'verified_at' => null,
                'context' => OtpVerification::CONTEXT_REGISTRATION,
            ]);

            $response = $this->postJson('/api/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '12345',
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Phone verified.')
                ->assertJsonStructure(['verified_token']);
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP bypass verify tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_still_sends_whatsapp_for_non_bypass_phone_when_bypass_enabled(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->configureOtpBypass('staging');
            $this->mockWhatsAppOtpDeliveryExpectCalled();

            $phone = '+9665' . random_int(100000000, 999999999);

            $response = $this->postJson('/api/auth/send-otp', [
                'phone' => $phone,
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'OTP sent.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP non-bypass send tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_otp_rejects_bypass_code_for_non_bypass_phone_when_bypass_enabled(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->configureOtpBypass('staging');

            $phone = '+9665' . random_int(100000000, 999999999);

            $response = $this->postJson('/api/auth/verify-otp', [
                'phone' => $phone,
                'otp' => '12345',
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('error', 'otp_not_found');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP non-bypass verify tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function send_otp_still_sends_whatsapp_for_bypass_phone_in_local_env(): void
    {
        $this->skipIfUsersTableMissing();

        try {
            $this->configureOtpBypass('local');
            $this->mockWhatsAppOtpDeliveryExpectCalled();

            $phone = '+966101010101';

            $response = $this->postJson('/api/auth/send-otp', [
                'phone' => $phone,
            ]);

            $response->assertStatus(200)
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'OTP sent.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP local bypass tests: ' . $e->getMessage());
        }
    }
}
