<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class OtpVerification extends Model
{
    public const CONTEXT_REGISTRATION = 'registration';

    public const MAX_SENDS_PER_HOUR = 5;

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
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'verified_at' => 'datetime',
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

        if ($rateLimit >= self::MAX_SENDS_PER_HOUR) {
            Log::info('OTP rate limit exceeded', [
                'user_id' => $user->id,
                'phone_masked' => self::maskPhone($user->phone),
                'context' => $context,
            ]);

            return ['success' => false, 'error' => 'rate_limit_exceeded'];
        }

        $plainOtp = (string) random_int(100000, 999999);
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
}
