<?php

namespace App\Domain\Affiliate\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Affiliate\Models\Affiliate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Affiliate Repository Interface
 * 
 * Defines the contract for Affiliate data access operations
 */
interface AffiliateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate affiliates with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get affiliates by request status
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status);

    /**
     * Update request status
     *
     * @param Affiliate $affiliate
     * @param string $status
     * @return Affiliate
     */
    public function updateRequestStatus(Affiliate $affiliate, string $status): Affiliate;
}

