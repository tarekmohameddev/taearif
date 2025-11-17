<?php

namespace App\Domain\Affiliate\Repositories;

use App\Domain\Affiliate\Models\Affiliate;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Affiliate Repository
 * 
 * Handles data access for Affiliate model
 */
class AffiliateRepository extends BaseRepository implements AffiliateRepositoryInterface
{
    /**
     * AffiliateRepository constructor.
     *
     * @param Affiliate $model
     */
    public function __construct(Affiliate $model)
    {
        parent::__construct($model);
    }

    /**
     * Search and paginate affiliates with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'transactions']);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Status filter
        if (!empty($filters['request_status'])) {
            $query->byStatus($filters['request_status']);
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
     * Get affiliates by request status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model
            ->byStatus($status)
            ->with(['user', 'transactions'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Update request status
     *
     * @param Affiliate $affiliate
     * @param string $status
     * @return Affiliate
     */
    public function updateRequestStatus(Affiliate $affiliate, string $status): Affiliate
    {
        $affiliate->update([
            'request_status' => $status,
        ]);

        return $affiliate->fresh(['user', 'transactions']);
    }

    /**
     * Apply search logic
     *
     * @param $query
     * @param string $search
     * @return mixed
     */
    protected function applySearch($query, string $search)
    {
        return $query->search($search);
    }
}

