<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Models\User;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * User Repository
 *
 * Handles data access for User model (Tenants)
 */
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * UserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model
            ->where('email', $email)
            ->first();
    }

    /**
     * Get users by plan
     *
     * @param string $planSlug
     * @return Collection
     */
    public function getByPlan(string $planSlug): Collection
    {
        return $this->model
            ->tenants()
            ->whereHas('activeMembership.package', function ($query) use ($planSlug) {
                $query->where('slug', $planSlug);
            })
            ->get();
    }

    /**
     * Get recent users
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->tenants()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get users with expiring subscriptions
     *
     * @param int $days
     * @return Collection
     */
    public function getExpiringSubscriptions(int $days = 7): Collection
    {
        $expiryDate = now()->addDays($days);

        return $this->model
            ->tenants()
            ->whereHas('activeMembership', function ($query) use ($expiryDate) {
                $query->where('expire_date', '<=', $expiryDate)
                      ->where('expire_date', '>=', now());
            })
            ->get();
    }

    /**
     * Get users with no content
     *
     * @return Collection
     */
    public function getUsersWithNoContent(): Collection
    {
        // This would depend on your content tables
        // For now, returning empty collection
        return collect([]);
    }

    /**
     * Search and paginate users with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->getTenants($filters, $perPage);
    }

    /**
     * Get all tenants with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTenants(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->tenants()->with(['referrer', 'activeMembership.package']);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Date range filter
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Referred by filter
        if (!empty($filters['referred_by'])) {
            $query->where('referred_by', $filters['referred_by']);
        }

        // Active status filter
        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        // Featured filter
        if (isset($filters['featured'])) {
            $query->where('featured', $filters['featured']);
        }

        // Subscription status filter
        if (array_key_exists('has_active_subscription', $filters)) {
            if ($filters['has_active_subscription']) {
                $query->whereHas('activeMembership');
            } else {
                $query->whereDoesntHave('activeMembership');
            }
        }

        if (!empty($filters['subscription_status'])) {
            $status = strtolower($filters['subscription_status']);
            if ($status === 'trial') {
                $query->whereHas('activeMembership', function ($q) {
                    $q->where('is_trial', true);
                });
            } elseif ($status === 'expired') {
                $query->whereDoesntHave('activeMembership')
                      ->whereHas('memberships', function ($q) {
                          $q->where('expire_date', '<', now());
                      });
            }
        }

        if (!empty($filters['plan'])) {
            $planFilter = $filters['plan'];
            $query->whereHas('activeMembership.package', function ($q) use ($planFilter) {
                if (is_numeric($planFilter)) {
                    $q->where('id', $planFilter);
                } else {
                    $q->where('slug', $planFilter);
                }
            });
        }

        // Order by
        $allowedOrderBy = ['created_at', 'username', 'company_name', 'email', 'status'];
        $orderBy = $filters['order_by'] ?? 'created_at';
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'created_at';
        }

        $orderDir = strtolower($filters['order_dir'] ?? 'desc');
        $orderDir = $orderDir === 'asc' ? 'asc' : 'desc';

        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Toggle user active status
     *
     * @param User $user
     * @return User
     */
    public function toggleActive(User $user): User
    {
        $user->active = !$user->active;
        $user->save();
        return $user;
    }

    /**
     * Toggle user featured status
     *
     * @param User $user
     * @return User
     */
    public function toggleFeatured(User $user): User
    {
        $user->featured = $user->featured ? 0 : 1;
        $user->save();
        return $user;
    }
}

