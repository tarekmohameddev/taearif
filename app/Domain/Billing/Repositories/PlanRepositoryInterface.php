<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\Plan;
use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Plan Repository Interface
 * 
 * Defines the contract for Plan data access operations
 */
interface PlanRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate plans with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get all plans with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPlans(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find plan by slug.
     *
     * @param string $slug
     * @return Plan|null
     */
    public function findBySlug(string $slug): ?Plan;

    /**
     * Toggle plan active status.
     *
     * @param Plan $plan
     * @return Plan
     */
    public function toggleActive(Plan $plan): Plan;

    /**
     * Toggle plan featured status.
     *
     * @param Plan $plan
     * @return Plan
     */
    public function toggleFeatured(Plan $plan): Plan;

}
