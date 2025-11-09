<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\Plan;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Plan Repository
 *
 * Handles data access for Plan model (packages table)
 */
class PlanRepository extends BaseRepository implements PlanRepositoryInterface
{
    /**
     * PlanRepository constructor.
     *
     * @param Plan $model
     */
    public function __construct(Plan $model)
    {
        parent::__construct($model);
    }

    /**
     * Find plan by slug
     *
     * @param string $slug
     * @return Plan|null
     */
    public function findBySlug(string $slug): ?Plan
    {
        return $this->model
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Get active plans
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->model
            ->active()
            ->orderBy('serial_number', 'asc')
            ->get();
    }

    /**
     * Get featured plans
     *
     * @return Collection
     */
    public function getFeatured(): Collection
    {
        return $this->model
            ->featured()
            ->active()
            ->orderBy('serial_number', 'asc')
            ->get();
    }

    /**
     * Search and paginate plans with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->getPlans($filters, $perPage);
    }

    /**
     * Get all plans with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPlans(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model
            ->newQuery()
            ->withCount([
                'memberships as active_memberships_count' => function ($membershipQuery) {
                    $membershipQuery->where('status', 1)
                        ->whereDate('expire_date', '>=', now()->toDateString());
                },
            ]);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Active filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Featured filter
        if (isset($filters['featured'])) {
            $query->where('featured', $filters['featured']);
        }

        // Trial filter
        if (isset($filters['is_trial'])) {
            $query->where('is_trial', $filters['is_trial']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'serial_number';
        $orderDir = $filters['order_dir'] ?? 'asc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Toggle plan active status
     *
     * @param Plan $plan
     * @return Plan
     */
    public function toggleActive(Plan $plan): Plan
    {
        $current = $plan->getRawOriginal('is_active');
        $next = $this->boolToEnumString(!$this->isTruthy($current));

        $this->model->newQuery()
            ->whereKey($plan->getKey())
            ->update(['is_active' => $next]);

        return $plan->refresh();
    }

    /**
     * Toggle plan featured status
     *
     * @param Plan $plan
     * @return Plan
     */
    public function toggleFeatured(Plan $plan): Plan
    {
        $current = $plan->getRawOriginal('featured');
        $next = $this->boolToEnumString(!$this->isTruthy($current));

        $this->model->newQuery()
            ->whereKey($plan->getKey())
            ->update(['featured' => $next]);

        return $plan->refresh();
    }

    /**
     * Determine boolean truthiness from mixed database values.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * Convert a boolean into the enum-compatible string representation.
     *
     * @param bool $value
     * @return string
     */
    protected function boolToEnumString(bool $value): string
    {
        return $value ? '1' : '0';
    }
}

