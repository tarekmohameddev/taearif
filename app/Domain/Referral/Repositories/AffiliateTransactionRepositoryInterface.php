<?php

namespace App\Domain\Referral\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Referral\Models\AffiliateTransaction;
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
     * Approve transaction
     *
     * @param AffiliateTransaction $transaction
     * @return AffiliateTransaction
     */
    public function approveTransaction(AffiliateTransaction $transaction): AffiliateTransaction;

    /**
     * Reject transaction
     *
     * @param AffiliateTransaction $transaction
     * @param string|null $note
     * @return AffiliateTransaction
     */
    public function rejectTransaction(AffiliateTransaction $transaction, ?string $note = null): AffiliateTransaction;

    /**
     * Mark transaction as paid
     *
     * @param AffiliateTransaction $transaction
     * @param string|null $note
     * @return AffiliateTransaction
     */
    public function markAsPaid(AffiliateTransaction $transaction, ?string $note = null): AffiliateTransaction;
}

