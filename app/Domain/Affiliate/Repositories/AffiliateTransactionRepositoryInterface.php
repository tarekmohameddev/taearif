<?php

namespace App\Domain\Affiliate\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Affiliate\Models\AffiliateTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Affiliate Transaction Repository Interface
 *
 * Defines the contract for AffiliateTransaction data access operations
 */
interface AffiliateTransactionRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate transactions with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get transactions by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type);

    /**
     * Get transactions for affiliate
     *
     * @param int $affiliateId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getForAffiliate(int $affiliateId);

    /**
     * Collect transaction (move pending to collected)
     *
     * @param AffiliateTransaction $transaction
     * @param string|null $note
     * @return AffiliateTransaction
     */
    public function collectTransaction(AffiliateTransaction $transaction, ?string $note = null): AffiliateTransaction;
}

