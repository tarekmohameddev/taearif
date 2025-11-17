<?php

namespace App\Domain\Affiliate\Repositories;

use App\Domain\Affiliate\Models\Affiliate;
use App\Domain\Shared\Repositories\BaseRepositoryInterface;

/**
 * Referral Repository Interface
 *
 * Contract for Referral partner data access operations
 */
interface ReferralRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find referral by code
     *
     * @param string $code
     * @return Affiliate|null
     */
    public function findByCode(string $code): ?Affiliate;

    /**
     * Get top performers
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopPerformers(int $limit = 10): \Illuminate\Database\Eloquent\Collection;
}

