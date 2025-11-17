<?php

namespace App\Domain\Affiliate\Repositories;

use App\Domain\Affiliate\Models\AffiliateTransaction;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Affiliate Transaction Repository
 * 
 * Handles data access for AffiliateTransaction model
 */
class AffiliateTransactionRepository extends BaseRepository implements AffiliateTransactionRepositoryInterface
{
    /**
     * AffiliateTransactionRepository constructor.
     *
     * @param AffiliateTransaction $model
     */
    public function __construct(AffiliateTransaction $model)
    {
        parent::__construct($model);
    }

    /**
     * Search and paginate transactions with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['affiliate.user', 'referredUser']);

        // Type filter
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        // Affiliate filter
        if (!empty($filters['affiliate_id'])) {
            $query->forAffiliate($filters['affiliate_id']);
        }

        // Amount range filter
        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }
        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Date range filter
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get transactions by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->model
            ->byType($type)
            ->with(['affiliate.user', 'referredUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get transactions for affiliate
     *
     * @param int $affiliateId
     * @return Collection
     */
    public function getForAffiliate(int $affiliateId): Collection
    {
        return $this->model
            ->forAffiliate($affiliateId)
            ->with(['referredUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Collect transaction (mark pending as collected)
     *
     * @param AffiliateTransaction $transaction
     * @param string|null $note
     * @return AffiliateTransaction
     */
    public function collectTransaction(AffiliateTransaction $transaction, ?string $note = null): AffiliateTransaction
    {
        $transaction->update([
            'type' => 'collected',
            'note' => $note ?? $transaction->note,
        ]);

        return $transaction->fresh(['affiliate.user', 'referredUser']);
    }
}

