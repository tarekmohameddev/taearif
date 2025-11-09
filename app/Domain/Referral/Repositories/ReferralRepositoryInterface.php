<?php

namespace App\Domain\Referral\Repositories;

use App\Domain\Referral\Models\Referral;
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
     * @return Referral|null
     */
    public function findByCode(string $code): ?Referral;

    /**
     * Get top performers
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTopPerformers(int $limit = 10): \Illuminate\Database\Eloquent\Collection;
}

