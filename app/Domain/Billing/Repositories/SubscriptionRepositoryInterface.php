<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Subscription Repository Interface
 * 
 * Defines the contract for Subscription data access operations
 */
interface SubscriptionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get active subscriptions count
     *
     * @return int
     */
    public function getActiveCount(): int;

    /**
     * Get expiring subscriptions
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExpiring(int $days = 7): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get subscriptions not renewed
     *
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNotRenewed(int $days = 30): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get trial subscriptions not upgraded
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrialNotUpgraded(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get all subscriptions with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getSubscriptions(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
