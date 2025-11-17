<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

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
                $query->select(DB::raw(1))
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
        $latestIds = DB::table('memberships')
            ->selectRaw('MAX(id) as id')
            ->whereNotNull('user_id')
            ->groupBy('user_id');

        $query = $this->model
            ->with(['user.generalSetting', 'package', 'latestInvoice'])
            ->whereHas('user')
            ->whereIn('id', $latestIds);

        // Unified search filter
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $likeTerm = '%' . $term . '%';

            $query->where(function ($q) use ($term, $likeTerm) {
                // Tenant (user) search
                $q->whereHas('user', function ($userQuery) use ($term, $likeTerm) {
                    $userQuery->where(function ($inner) use ($likeTerm) {
                        $inner->where('first_name', 'like', $likeTerm)
                            ->orWhere('last_name', 'like', $likeTerm)
                            ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", [$likeTerm])
                            ->orWhere('username', 'like', $likeTerm)
                            ->orWhere('email', 'like', $likeTerm)
                            ->orWhere('company_name', 'like', $likeTerm);
                    })->orWhereHas('generalSetting', function ($settingQuery) use ($likeTerm) {
                        $settingQuery->where('site_name', 'like', $likeTerm);
                    });
                });

                // Plan search
                $q->orWhereHas('package', function ($planQuery) use ($likeTerm) {
                    $planQuery->where('title', 'like', $likeTerm)
                        ->orWhere('slug', 'like', $likeTerm);
                });

                // Price search
                if (is_numeric($term)) {
                    $numericTerm = (float) $term;
                    $q->orWhere('package_price', $numericTerm)
                        ->orWhere('price', $numericTerm);
                } else {
                    $q->orWhere('package_price', 'like', $likeTerm)
                        ->orWhere('price', 'like', $likeTerm);
                }

                // Status search (English & Arabic keywords)
                $statusMap = [
                    'active' => 1,
                    'expired' => 0,
                    'inactive' => 0,
                    'trial' => 'trial',
                ];
                $statusMapAr = [
                    'نشط' => 1,
                    'فعال' => 1,
                    'مفعل' => 1,
                    'منتهي' => 0,
                    'منتهية' => 0,
                    'غير نشط' => 0,
                    'تجريبي' => 'trial',
                    'تجريبية' => 'trial',
                ];

                $normalizedTerm = strtolower($term);
                $statusSearch = $statusMap[$normalizedTerm] ?? ($statusMapAr[$term] ?? null);
                if ($statusSearch === 'trial') {
                    $q->orWhere('is_trial', true);
                } elseif ($statusSearch !== null) {
                    $q->orWhere('status', $statusSearch);
                }

                // Upcoming billing (expire date) search
                $date = date_create($term);
                if ($date !== false) {
                    $formattedDate = $date->format('Y-m-d');
                    $q->orWhereDate('expire_date', $formattedDate)
                        ->orWhereDate('start_date', $formattedDate);
                }

                // Payment method search
                $q->orWhere('payment_method', 'like', $likeTerm);
            });
        }

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

    /**
     * Get latest subscription for a specific user.
     *
     * @param int $userId
     * @return Subscription|null
     */
    public function getLatestForUser(int $userId): ?Subscription
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('expire_date')
            ->orderByDesc('created_at')
            ->with(['user.generalSetting', 'package', 'latestInvoice'])
            ->first();
    }
}

