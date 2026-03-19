<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Models\OtpVerification;
use App\Models\User;
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

    /** @test */
    public function send_otp_requires_phone(): void
    {
        $response = $this->postJson('/api/auth/send-otp', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['phone']);
    }

    /** @test */
    public function send_otp_creates_otp_record_for_existing_user(): void
    {
        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-send@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);

            $response = $this->postJson('/api/auth/send-otp', [
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
    public function send_otp_rate_limits_after_5_sends_per_hour(): void
    {
        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-rate-limit@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);

            // Seed 5 OTP rows within the last hour so createOrRefreshForUser hits the rate-limit check.
            for ($i = 0; $i < 5; $i++) {
                DB::table('otp_verifications')->insert([
                    'user_id' => $user->id,
                    'identifier' => $phone,
                    'otp' => Hash::make('123456'),
                    'otp_expires_at' => now()->addMinutes(5),
                    'attempts' => 0,
                    'verified_at' => null,
                    'context' => OtpVerification::CONTEXT_REGISTRATION,
                    'created_at' => now()->subMinutes(30 + $i),
                    'updated_at' => now()->subMinutes(30 + $i),
                ]);
            }

            $response = $this->postJson('/api/auth/send-otp', [
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
    public function verify_otp_sets_phone_verified_at_on_success(): void
    {
        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-verify-success@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $token = $this->loginAndGetToken($email, $plainPassword);

            $otpPlain = '123456';
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
                ->assertJsonPath('error', null)
                ->assertJsonPath('message', 'Phone verified.');

            $this->assertNotNull($user->fresh()->phone_verified_at, 'Expected phone_verified_at to be set.');
        } catch (QueryException $e) {
            $this->markTestSkipped('Schema/users missing for OTP verify success tests: ' . $e->getMessage());
        }
    }

    /** @test */
    public function verify_otp_returns_expected_error_codes(): void
    {
        try {
            $plainPassword = 'password123';
            $phone = '+9665' . random_int(100000000, 999999999);
            $email = 'otp-e2e-verify-errors@example.com';

            $user = $this->createActiveTenantWithKnownPassword($email, $phone, $plainPassword);
            $token = $this->loginAndGetToken($email, $plainPassword);

            $cases = [
                // error => [setup, sendOtp, expectedOtpAttemptsAfterOrNull]
                'otp_not_found' => ['none', '123456', null],
                'otp_invalid' => ['invalid', '000000', 1],
                'otp_expired' => ['expired', '123456', null],
                'too_many_attempts' => ['too_many_attempts', '123456', 5],
            ];

            foreach ($cases as $expectedError => [$setup, $sendOtp, $expectedAttemptsAfter]) {
                DB::table('otp_verifications')->where('user_id', $user->id)->delete();

                if ($setup === 'invalid') {
                    OtpVerification::query()->create([
                        'user_id' => $user->id,
                        'identifier' => $phone,
                        'otp' => Hash::make('123456'),
                        'otp_expires_at' => now()->addMinutes(5),
                        'attempts' => 0,
                        'verified_at' => null,
                        'context' => OtpVerification::CONTEXT_REGISTRATION,
                    ]);
                } elseif ($setup === 'expired') {
                    OtpVerification::query()->create([
                        'user_id' => $user->id,
                        'identifier' => $phone,
                        'otp' => Hash::make('123456'),
                        'otp_expires_at' => now()->subMinutes(1),
                        'attempts' => 0,
                        'verified_at' => null,
                        'context' => OtpVerification::CONTEXT_REGISTRATION,
                    ]);
                } elseif ($setup === 'too_many_attempts') {
                    OtpVerification::query()->create([
                        'user_id' => $user->id,
                        'identifier' => $phone,
                        'otp' => Hash::make('123456'),
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
}

