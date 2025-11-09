<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Subscription Repository
 *
 * Handles data access for Subscription model (memberships table)
 */
class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    /**
     * SubscriptionRepository constructor.
     *
     * @param Subscription $model
     */
    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    /**
     * Get active subscriptions count
     *
     * @return int
     */
    public function getActiveCount(): int
    {
        return $this->model->active()->count();
    }

    /**
     * Get expiring subscriptions
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiring(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->expiringSoon($days)
            ->with(['user', 'package'])
            ->get();
    }

    /**
     * Get subscriptions not renewed
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotRenewed(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        $expiredDate = now()->subDays($days)->toDateString();

        return $this->model
            ->where('status', 1)
            ->where('expire_date', '<', $expiredDate)
            ->whereNotExists(function ($query) {
                $query->select(\DB::raw(1))
                      ->from('memberships as m2')
                      ->whereColumn('m2.user_id', 'memberships.user_id')
                      ->where('m2.status', 1)
                      ->where('m2.expire_date', '>=', now()->toDateString());
            })
            ->with(['user', 'package'])
            ->get();
    }

    /**
     * Get trial subscriptions not upgraded
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrialNotUpgraded(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model
            ->trial()
            ->where('status', 1)
            ->where('expire_date', '<', now()->toDateString())
            ->with(['user', 'package'])
            ->get();
    }

    /**
     * Get all subscriptions with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSubscriptions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'package']);

        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'expired') {
                $query->expired();
            } elseif ($filters['status'] === 'trial') {
                $query->trial();
            }
        }

        // Date range filter
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Expiration date filter
        if (!empty($filters['expire_start'])) {
            $query->where('expire_date', '>=', $filters['expire_start']);
        }
        if (!empty($filters['expire_end'])) {
            $query->where('expire_date', '<=', $filters['expire_end']);
        }

        // Package filter
        if (!empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }
}

