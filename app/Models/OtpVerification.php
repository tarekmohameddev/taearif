<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OtpVerification extends Model
{
    public const CONTEXT_REGISTRATION = 'registration';

    public const DEFAULT_MAX_SENDS_PER_HOUR = 5;

    public const MAX_ATTEMPTS = 5;

    public const OTP_EXPIRY_MINUTES = 5;

    protected $table = 'otp_verifications';

    protected $fillable = [
        'user_id',
        'identifier',
        'otp',
        'otp_expires_at',
        'attempts',
        'verified_at',
        'context',
        'verified_token',
        'verified_token_expires_at',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'verified_token_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create or refresh OTP for user. Returns ['success' => true, 'otp' => string] or ['success' => false, 'error' => string].
     *
     * @param  array{success: bool, otp?: string, error?: string}  $result
     */
    public static function createOrRefreshForUser(User $user, string $context = self::CONTEXT_REGISTRATION): array
    {
        $rateLimit = self::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($rateLimit >= self::maxSendsPerHour($context)) {
            Log::info('OTP rate limit exceeded', [
                'user_id' => $user->id,
                'phone_masked' => self::maskPhone($user->phone),
                'context' => $context,
            ]);

            return ['success' => false, 'error' => 'rate_limit_exceeded'];
        }

        $plainOtp = (string) random_int(10000, 99999);
        $hashedOtp = Hash::make($plainOtp);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        self::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'context' => $context,
            ],
            [
                'identifier' => $user->phone,
                'otp' => $hashedOtp,
                'otp_expires_at' => $expiresAt,
                'attempts' => 0,
                'verified_at' => null,
            ]
        );

        Log::info('OTP created/refreshed', [
            'user_id' => $user->id,
            'phone_masked' => self::maskPhone($user->phone),
            'context' => $context,
        ]);

        return ['success' => true, 'otp' => $plainOtp];
    }

    /**
     * Create or refresh OTP for a phone (pre-registration flow).
     *
     * Stores OTP without requiring an existing user:
     * - user_id = null
     * - identifier = phone
     */
    public static function createOrRefreshForPhone(string $phone, string $context = self::CONTEXT_REGISTRATION): array
    {
        $rateLimit = self::query()
            ->whereNull('user_id')
            ->where('identifier', $phone)
            ->where('context', $context)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($rateLimit >= self::maxSendsPerHour($context)) {
            Log::info('OTP rate limit exceeded', [
                'identifier' => self::maskPhone($phone),
                'context' => $context,
            ]);

            return ['success' => false, 'error' => 'rate_limit_exceeded'];
        }

        $plainOtp = (string) random_int(10000, 99999);
        $hashedOtp = Hash::make($plainOtp);
        $expiresAt = now()->addMinutes(self::OTP_EXPIRY_MINUTES);

        self::query()->updateOrCreate(
            [
                'user_id' => null,
                'identifier' => $phone,
                'context' => $context,
            ],
            [
                'otp' => $hashedOtp,
                'otp_expires_at' => $expiresAt,
                'attempts' => 0,
                'verified_at' => null,
                'verified_token' => null,
                'verified_token_expires_at' => null,
            ]
        );

        return ['success' => true, 'otp' => $plainOtp];
    }

    /**
     * Verify OTP for a phone (pre-registration flow).
     *
     * On success, stores a short-lived verified_token to be consumed in `/api/register`.
     */
    public static function verifyForPhone(string $phone, string $plainOtp, string $context = self::CONTEXT_REGISTRATION): array
    {
        $record = self::query()
            ->whereNull('user_id')
            ->where('identifier', $phone)
            ->where('context', $context)
            ->whereNull('verified_at')
            ->first();

        if (!$record) {
            return ['result' => 'otp_not_found'];
        }

        if (self::isTestBypassOtp($plainOtp, $context)) {
            $verifiedToken = (string) Str::uuid();
            $verifiedTokenExpiresAt = now()->addMinutes(15);

            $record->update([
                'verified_at' => now(),
                'verified_token' => $verifiedToken,
                'verified_token_expires_at' => $verifiedTokenExpiresAt,
            ]);

            Log::warning('OTP test bypass used', [
                'identifier' => self::maskPhone($phone),
                'context' => $context,
            ]);

            return ['result' => 'ok', 'verified_token' => $verifiedToken];
        }

        if ($record->otp_expires_at->isPast()) {
            return ['result' => 'otp_expired'];
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return ['result' => 'too_many_attempts'];
        }

        if (!Hash::check($plainOtp, $record->otp)) {
            $record->increment('attempts');
            return ['result' => 'otp_invalid'];
        }

        $verifiedToken = (string) Str::uuid();
        $verifiedTokenExpiresAt = now()->addMinutes(15);

        $record->update([
            'verified_at' => now(),
            'verified_token' => $verifiedToken,
            'verified_token_expires_at' => $verifiedTokenExpiresAt,
        ]);

        return ['result' => 'ok', 'verified_token' => $verifiedToken];
    }

    /**
     * Verify OTP for user. Returns 'ok' on success or error code: otp_not_found, otp_expired, too_many_attempts, otp_invalid.
     */
    public static function verifyForUser(User $user, string $plainOtp, string $context = self::CONTEXT_REGISTRATION): string
    {
        $record = self::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->whereNull('verified_at')
            ->first();

        if (!$record) {
            return 'otp_not_found';
        }

        if (self::isTestBypassOtp($plainOtp, $context)) {
            $record->update(['verified_at' => now()]);

            Log::warning('OTP test bypass used', [
                'user_id' => $user->id,
                'phone_masked' => self::maskPhone($user->phone),
                'context' => $context,
            ]);

            return 'ok';
        }

        if ($record->otp_expires_at->isPast()) {
            return 'otp_expired';
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return 'too_many_attempts';
        }

        if (!Hash::check($plainOtp, $record->otp)) {
            $record->increment('attempts');

            return 'otp_invalid';
        }

        $record->update(['verified_at' => now()]);

        return 'ok';
    }

    protected static function maskPhone(?string $phone): string
    {
        if ($phone === null || strlen($phone) < 4) {
            return '****';
        }

        return '****' . substr($phone, -4);
    }

    protected static function maxSendsPerHour(string $context): int
    {
        if ($context === self::CONTEXT_REGISTRATION) {
            $value = null;
            try {
                if (Schema::hasTable('basic_settings')) {
                    $value = (int) (BasicSetting::query()->value('otp_max_sends_per_hour') ?? 0);
                }
            } catch (\Throwable $e) {
                // ignore DB/bootstrap issues and fallback to config default
            }

            if ($value <= 0) {
                $value = (int) config('api.otp.registration.max_sends_per_hour', self::DEFAULT_MAX_SENDS_PER_HOUR);
            }
            return max(1, $value);
        }

        return self::DEFAULT_MAX_SENDS_PER_HOUR;
    }

    protected static function isTestBypassOtp(string $plainOtp, string $context): bool
    {
        if ($context !== self::CONTEXT_REGISTRATION) {
            return false;
        }

        if (app()->environment('production')) {
            // Production-only hardcoded bypass requested for emergency access.
            // Keep narrowly scoped to the registration context.
            return hash_equals('12345', $plainOtp);
        }

        $enabled = (bool) config('api.otp.registration.test_bypass_enabled', false);
        $code = (string) config('api.otp.registration.test_bypass_code', '');

        if (!$enabled || $code === '') {
            return false;
        }

        return hash_equals($code, $plainOtp);
    }
}
