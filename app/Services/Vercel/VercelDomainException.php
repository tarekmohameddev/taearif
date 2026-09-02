<?php

namespace App\Services\Vercel;

use RuntimeException;

class VercelDomainException extends RuntimeException
{
    public const CODE_CAPACITY_REACHED = 'CAPACITY_REACHED';

    public const CODE_OWNERSHIP_REQUIRED = 'OWNERSHIP_REQUIRED';

    public const CODE_INVALID_DOMAIN = 'INVALID_DOMAIN';

    public const CODE_UNAUTHORIZED = 'UNAUTHORIZED';

    public const CODE_NOT_CONFIGURED = 'NOT_CONFIGURED';

    public const CODE_RATE_LIMITED = 'RATE_LIMITED';

    public const CODE_TRANSFER_CONFLICT = 'TRANSFER_CONFLICT';

    public const CODE_VERIFICATION_PENDING = 'VERIFICATION_PENDING';

    public const CODE_PROVIDER_UNAVAILABLE = 'PROVIDER_UNAVAILABLE';

    public const CODE_REDIRECT_MISMATCH = 'REDIRECT_MISMATCH';

    public const CODE_PROJECT_IDENTITY_MISMATCH = 'PROJECT_IDENTITY_MISMATCH';

    public const CODE_MUTATION_BLOCKED = 'MUTATION_BLOCKED';

    public const CODE_CONFIRMATION_REQUIRED = 'CONFIRMATION_REQUIRED';

    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly mixed $responseBody = null,
        public readonly ?string $internalCode = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getErrorCode(): ?string
    {
        if (! is_array($this->responseBody)) {
            return null;
        }

        $code = $this->responseBody['error']['code'] ?? null;

        return is_string($code) ? $code : null;
    }

    public static function mapProviderCode(?string $providerCode, int $status): ?string
    {
        if ($providerCode !== null) {
            return match ($providerCode) {
                'project_domain_limit_reached' => self::CODE_CAPACITY_REACHED,
                'domain_already_in_use',
                'domain_in_use',
                'domain_already_owned',
                'domain_not_verified' => self::CODE_OWNERSHIP_REQUIRED,
                'invalid_domain',
                'invalid_domain_name' => self::CODE_INVALID_DOMAIN,
                'forbidden',
                'not_authorized',
                'not_authenticated' => self::CODE_UNAUTHORIZED,
                'rate_limited',
                'too_many_requests' => self::CODE_RATE_LIMITED,
                'domain_transfer_conflict',
                'domain_is_being_transferred' => self::CODE_TRANSFER_CONFLICT,
                'verification_pending',
                'missing_verification' => self::CODE_VERIFICATION_PENDING,
                default => null,
            };
        }

        if ($status === 429) {
            return self::CODE_RATE_LIMITED;
        }

        if ($status === 401 || $status === 403) {
            return self::CODE_UNAUTHORIZED;
        }

        if ($status >= 500) {
            return self::CODE_PROVIDER_UNAVAILABLE;
        }

        return null;
    }
}
