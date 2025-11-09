<?php

namespace App\Domain\Admin\Repositories;

use App\Domain\Admin\Models\AdminImpersonation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Impersonation Repository Interface
 *
 * Defines data access contract for admin impersonations
 */
interface ImpersonationRepositoryInterface
{
    /**
     * Create a new impersonation record.
     *
     * @param array $data
     * @return AdminImpersonation
     */
    public function create(array $data): AdminImpersonation;

    /**
     * Find impersonation by ID.
     *
     * @param int $id
     * @return AdminImpersonation|null
     */
    public function find(int $id): ?AdminImpersonation;

    /**
     * Find active impersonation by token ID.
     *
     * @param int $tokenId
     * @return AdminImpersonation|null
     */
    public function findByTokenId(int $tokenId): ?AdminImpersonation;

    /**
     * Find active impersonation for a specific admin-user pair.
     *
     * @param int $adminId
     * @param int $userId
     * @return AdminImpersonation|null
     */
    public function findActiveByAdminAndUser(int $adminId, int $userId): ?AdminImpersonation;

    /**
     * Get all active impersonations.
     *
     * @return Collection
     */
    public function getActive(): Collection;

    /**
     * Get impersonation history with filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Get impersonations by admin.
     *
     * @param int $adminId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAdmin(int $adminId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get impersonations by user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Update impersonation record.
     *
     * @param int $id
     * @param array $data
     * @return AdminImpersonation
     */
    public function update(int $id, array $data): AdminImpersonation;

    /**
     * End impersonation session.
     *
     * @param AdminImpersonation $impersonation
     * @return AdminImpersonation
     */
    public function endSession(AdminImpersonation $impersonation): AdminImpersonation;

    /**
     * Mark expired impersonations as expired.
     *
     * @param int $hoursLimit
     * @return int Number of expired sessions
     */
    public function markExpiredSessions(int $hoursLimit = 1): int;

    /**
     * Increment actions count for impersonation.
     *
     * @param int $tokenId
     * @return void
     */
    public function incrementActionsCount(int $tokenId): void;
}

