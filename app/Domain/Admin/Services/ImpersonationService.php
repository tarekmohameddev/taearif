<?php

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\AdminImpersonation;
use App\Domain\Admin\Repositories\ImpersonationRepositoryInterface;
use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ImpersonationException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Impersonation Service
 *
 * Handles admin impersonation business logic
 */
class ImpersonationService extends BaseService
{
    /**
     * @var ImpersonationRepositoryInterface
     */
    protected ImpersonationRepositoryInterface $impersonationRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;

    /**
     * Default token expiration in hours.
     *
     * @var int
     */
    protected int $tokenExpirationHours = 1;

    /**
     * ImpersonationService constructor.
     *
     * @param ImpersonationRepositoryInterface $impersonationRepository
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        ImpersonationRepositoryInterface $impersonationRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->impersonationRepository = $impersonationRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Start impersonation session.
     *
     * @param Admin $admin
     * @param int $userId
     * @param string|null $reason
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array ['impersonation' => AdminImpersonation, 'token' => string]
     * @throws ImpersonationException
     * @throws ResourceNotFoundException
     */
    public function startImpersonation(
        Admin $admin,
        int $userId,
        ?string $reason = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        // Find user
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new ResourceNotFoundException('User not found');
        }

        // Validate impersonation
        $this->validateImpersonation($admin, $user);

        // Check for existing active session
        if ($this->impersonationRepository->findActiveByAdminAndUser($admin->id, $user->id)) {
            throw new ImpersonationException(
                'You already have an active impersonation session for this user',
                'IMPERSONATION_ALREADY_ACTIVE',
                400
            );
        }

        return $this->transaction(function () use ($admin, $user, $reason, $ipAddress, $userAgent) {
            // Create Sanctum token for the user
            $expiresAt = now()->addHours($this->tokenExpirationHours);

            $tokenResult = $user->createToken(
                "impersonated-by-admin-{$admin->id}",
                ['*'],
                $expiresAt
            );

            $plainTextToken = $tokenResult->plainTextToken;
            $tokenId = $tokenResult->accessToken->id;

            // Create impersonation record
            $impersonation = $this->impersonationRepository->create([
                'admin_id' => $admin->id,
                'user_id' => $user->id,
                'token_id' => $tokenId,
                'started_at' => now(),
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'reason' => $reason,
                'status' => 'active',
            ]);

            return [
                'impersonation' => $impersonation->fresh(['admin', 'user']),
                'token' => $plainTextToken,
                'expires_at' => $expiresAt,
            ];
        });
    }

    /**
     * End impersonation session by token.
     *
     * @param string $token
     * @return AdminImpersonation
     * @throws ImpersonationException
     */
    public function endImpersonation(string $token): AdminImpersonation
    {
        // Parse token ID from plain text token (format: "tokenId|hash")
        $tokenId = explode('|', $token)[0] ?? null;

        if (!$tokenId) {
            throw new ImpersonationException(
                'Invalid token format',
                'INVALID_TOKEN',
                400
            );
        }

        // Find impersonation by token ID
        $impersonation = $this->impersonationRepository->findByTokenId($tokenId);

        if (!$impersonation) {
            throw new ImpersonationException(
                'No active impersonation session found for this token',
                'IMPERSONATION_NOT_FOUND',
                404
            );
        }

        return $this->transaction(function () use ($impersonation, $tokenId) {
            // Revoke the Sanctum token
            PersonalAccessToken::find($tokenId)?->delete();

            // End the impersonation session
            return $this->impersonationRepository->endSession($impersonation);
        });
    }

    /**
     * Get all active impersonations.
     *
     * @return Collection
     */
    public function getActiveImpersonations(): Collection
    {
        return $this->impersonationRepository->getActive();
    }

    /**
     * Get impersonation history with filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getImpersonationHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->impersonationRepository->getHistory($filters, $perPage);
    }

    /**
     * Get impersonation history for a specific user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws ResourceNotFoundException
     */
    public function getUserImpersonationHistory(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new ResourceNotFoundException('User not found');
        }

        return $this->impersonationRepository->getByUser($user->id, $perPage);
    }

    /**
     * Mark expired impersonation sessions.
     *
     * @return int Number of expired sessions
     */
    public function expireOldSessions(): int
    {
        return $this->transaction(function () {
            return $this->impersonationRepository->markExpiredSessions($this->tokenExpirationHours);
        });
    }

    /**
     * End impersonation session for the given admin.
     * If $tokenId is provided, it will target that specific session after validating ownership.
     * Otherwise, it will end the most recent active session for this admin.
     *
     * @param Admin $admin
     * @param int|null $tokenId
     * @return AdminImpersonation
     * @throws ImpersonationException
     */
    public function endImpersonationForAdmin(Admin $admin, ?int $tokenId = null): AdminImpersonation
    {
        if ($tokenId) {
            $impersonation = $this->impersonationRepository->findByTokenId($tokenId);
            if (
                !$impersonation ||
                $impersonation->status !== 'active' ||
                (int) $impersonation->admin_id !== (int) $admin->id
            ) {
                throw new ImpersonationException(
                    'No active impersonation session found for this admin and token',
                    'IMPERSONATION_NOT_FOUND',
                    404
                );
            }
        } else {
            $active = $this->impersonationRepository->getActive();
            $impersonation = $active
                ->where('admin_id', $admin->id)
                ->sortByDesc('started_at')
                ->first();

            if (!$impersonation) {
                throw new ImpersonationException(
                    'No active impersonation session found for this admin',
                    'IMPERSONATION_NOT_FOUND',
                    404
                );
            }
        }

        return $this->transaction(function () use ($impersonation) {
            // Revoke the Sanctum token
            PersonalAccessToken::find($impersonation->token_id)?->delete();

            // End the impersonation session
            return $this->impersonationRepository->endSession($impersonation);
        });
    }

    /**
     * Validate if admin can impersonate user.
     *
     * @param Admin $admin
     * @param User $user
     * @return void
     * @throws ImpersonationException
     */
    protected function validateImpersonation(Admin $admin, User $user): void
    {
        // Cannot impersonate if user account type is not 'tenant'
        if ($user->account_type !== 'tenant') {
            throw new ImpersonationException(
                'Can only impersonate tenant users',
                'INVALID_USER_TYPE',
                403
            );
        }

        // Cannot impersonate yourself (if admin also has a user account)
        if ($admin->email === $user->email) {
            throw new ImpersonationException(
                'Cannot impersonate yourself',
                'IMPERSONATE_SELF',
                403
            );
        }

        // Additional business rules can be added here:
        // - Cannot impersonate users with higher subscription tier
        // - Cannot impersonate users from specific countries
        // - etc.
    }

    /**
     * Increment actions count for current impersonation.
     *
     * @param string $token
     * @return void
     */
    public function trackAction(string $token): void
    {
        $tokenId = explode('|', $token)[0] ?? null;

        if ($tokenId) {
            $this->impersonationRepository->incrementActionsCount($tokenId);
        }
    }
}

