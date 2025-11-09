<?php

namespace App\Domain\Admin\Repositories;

use App\Domain\Admin\Models\AdminImpersonation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Impersonation Repository
 *
 * Handles data access for admin impersonations
 */
class ImpersonationRepository implements ImpersonationRepositoryInterface
{
    /**
     * @var AdminImpersonation
     */
    protected AdminImpersonation $model;

    /**
     * ImpersonationRepository constructor.
     *
     * @param AdminImpersonation $model
     */
    public function __construct(AdminImpersonation $model)
    {
        $this->model = $model;
    }

    /**
     * Create a new impersonation record.
     *
     * @param array $data
     * @return AdminImpersonation
     */
    public function create(array $data): AdminImpersonation
    {
        return $this->model->create($data);
    }

    /**
     * Find impersonation by ID.
     *
     * @param int $id
     * @return AdminImpersonation|null
     */
    public function find(int $id): ?AdminImpersonation
    {
        return $this->model->with(['admin', 'user'])->find($id);
    }

    /**
     * Find active impersonation by token ID.
     *
     * @param int $tokenId
     * @return AdminImpersonation|null
     */
    public function findByTokenId(int $tokenId): ?AdminImpersonation
    {
        return $this->model
            ->active()
            ->where('token_id', $tokenId)
            ->with(['admin', 'user'])
            ->first();
    }

    /**
     * Find active impersonation for a specific admin-user pair.
     *
     * @param int $adminId
     * @param int $userId
     * @return AdminImpersonation|null
     */
    public function findActiveByAdminAndUser(int $adminId, int $userId): ?AdminImpersonation
    {
        return $this->model
            ->active()
            ->where('admin_id', $adminId)
            ->where('user_id', $userId)
            ->with(['admin', 'user'])
            ->first();
    }

    /**
     * Get all active impersonations.
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->model
            ->active()
            ->with(['admin', 'user'])
            ->orderBy('started_at', 'desc')
            ->get();
    }

    /**
     * Get impersonation history with filters and pagination.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getHistory(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['admin', 'user']);

        // Filter by admin
        if (!empty($filters['admin_id'])) {
            $query->byAdmin($filters['admin_id']);
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['start_date']) || !empty($filters['end_date'])) {
            $query->dateRange(
                $filters['start_date'] ?? null,
                $filters['end_date'] ?? null
            );
        }

        // Active only
        if (isset($filters['active_only']) && $filters['active_only']) {
            $query->active();
        }

        // Ended only
        if (isset($filters['ended_only']) && $filters['ended_only']) {
            $query->ended();
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'started_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get impersonations by admin.
     *
     * @param int $adminId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAdmin(int $adminId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byAdmin($adminId)
            ->with(['user'])
            ->orderBy('started_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get impersonations by user.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byUser($userId)
            ->with(['admin'])
            ->orderBy('started_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Update impersonation record.
     *
     * @param int $id
     * @param array $data
     * @return AdminImpersonation
     */
    public function update(int $id, array $data): AdminImpersonation
    {
        $impersonation = $this->model->findOrFail($id);
        $impersonation->update($data);
        return $impersonation->fresh(['admin', 'user']);
    }

    /**
     * End impersonation session.
     *
     * @param AdminImpersonation $impersonation
     * @return AdminImpersonation
     */
    public function endSession(AdminImpersonation $impersonation): AdminImpersonation
    {
        $impersonation->endSession();
        return $impersonation->fresh(['admin', 'user']);
    }

    /**
     * Mark expired impersonations as expired.
     *
     * @param int $hoursLimit
     * @return int Number of expired sessions
     */
    public function markExpiredSessions(int $hoursLimit = 1): int
    {
        $cutoffTime = now()->subHours($hoursLimit);

        return $this->model
            ->active()
            ->where('started_at', '<=', $cutoffTime)
            ->update([
                'ended_at' => now(),
                'status' => 'expired',
            ]);
    }

    /**
     * Increment actions count for impersonation.
     *
     * @param int $tokenId
     * @return void
     */
    public function incrementActionsCount(int $tokenId): void
    {
        $impersonation = $this->findByTokenId($tokenId);

        if ($impersonation) {
            $impersonation->incrementActions();
        }
    }
}

