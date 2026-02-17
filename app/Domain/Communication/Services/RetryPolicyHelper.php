<?php

namespace App\Domain\Communication\Services;

use Carbon\Carbon;

class RetryPolicyHelper
{
    public function isTransient(string $provider, ?int $httpStatus, ?string $errorCode, ?string $errorMessage): bool
    {
        $provider = strtolower($provider);
        $code = strtolower((string) $errorCode);
        $msg = strtolower((string) $errorMessage);

        if ($httpStatus !== null) {
            if ($httpStatus === 429) {
                return true;
            }
            if ($httpStatus >= 500 && $httpStatus < 600) {
                return true;
            }
            if ($httpStatus >= 400 && $httpStatus < 500 && $httpStatus !== 429) {
                return false;
            }
        }

        if ($provider === 'meta') {
            if ($this->isMetaNonTransient($code, $msg)) {
                return false;
            }
            return $httpStatus === 429 || ($httpStatus !== null && $httpStatus >= 500);
        }

        if ($provider === 'evolution') {
            if ($this->isEvolutionNonTransient($code, $msg)) {
                return false;
            }
            return $httpStatus === 429 || ($httpStatus !== null && $httpStatus >= 500);
        }

        if (in_array($provider, ['sms', 'twilio', 'unifonic', 'gateway'], true)) {
            if ($this->isSmsNonTransient($code, $msg)) {
                return false;
            }
            return $httpStatus === 429 || ($httpStatus !== null && $httpStatus >= 500);
        }

        return $httpStatus === 429 || ($httpStatus !== null && $httpStatus >= 500);
    }

    public function nextRetryAt(int $attemptNumber): Carbon
    {
        $initial = (int) config('communication.reliability.retry.initial_backoff_seconds', 30);
        $maxBackoff = (int) config('communication.reliability.retry.max_backoff_seconds', 600);
        $seconds = min($initial * (2 ** $attemptNumber), $maxBackoff);
        return Carbon::now()->addSeconds($seconds);
    }

    public function maxAttempts(): int
    {
        return (int) config('communication.reliability.retry.max_attempts', 3);
    }

    private function isMetaNonTransient(string $code, string $msg): bool
    {
        $nonTransient = [
            'invalid_oauth', 'access_denied', 'permission_denied', 'auth', 'token',
            'invalid_phone', 'invalid_recipient', 'parameter', 'template', 'payload',
            'media', 'unsupported',
        ];
        foreach ($nonTransient as $needle) {
            if (str_contains($code, $needle) || str_contains($msg, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function isEvolutionNonTransient(string $code, string $msg): bool
    {
        $nonTransient = [
            'invalid_api', 'apikey', 'instance', 'account', 'validation', 'unauthorized',
        ];
        foreach ($nonTransient as $needle) {
            if (str_contains($code, $needle) || str_contains($msg, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function isSmsNonTransient(string $code, string $msg): bool
    {
        $nonTransient = [
            'invalid_credentials', 'invalid_recipient', 'invalid_content', 'reject', 'invalid',
        ];
        foreach ($nonTransient as $needle) {
            if (str_contains($code, $needle) || str_contains($msg, $needle)) {
                return true;
            }
        }
        return false;
    }
}
