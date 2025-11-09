<?php

namespace App\Domain\Domain\Repositories;

use App\Domain\Domain\Models\CustomDomain;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Custom Domain Repository
 * 
 * Handles data access for CustomDomain model
 */
class CustomDomainRepository extends BaseRepository implements CustomDomainRepositoryInterface
{
    /**
     * CustomDomainRepository constructor.
     *
     * @param CustomDomain $model
     */
    public function __construct(CustomDomain $model)
    {
        parent::__construct($model);
    }

    /**
     * Search and paginate domains with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['user']);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'inactive') {
                $query->inactive();
            } elseif ($filters['status'] === 'pending') {
                $query->pending();
            } elseif ($filters['status'] === 'approved') {
                $query->approved();
            }
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
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
     * Get pending domain requests
     *
     * @return Collection
     */
    public function getPending(): Collection
    {
        return $this->model
            ->pending()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get approved domains
     *
     * @return Collection
     */
    public function getApproved(): Collection
    {
        return $this->model
            ->approved()
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get domains by user
     *
     * @param int $userId
     * @return Collection
     */
    public function getByUser(int $userId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Approve domain request
     *
     * @param CustomDomain $domain
     * @return CustomDomain
     */
    public function approveDomain(CustomDomain $domain): CustomDomain
    {
        $domain->update([
            'current_domain' => $domain->requested_domain,
            'status' => true,
        ]);

        return $domain->fresh(['user']);
    }

    /**
     * Reject domain request
     *
     * @param CustomDomain $domain
     * @return CustomDomain
     */
    public function rejectDomain(CustomDomain $domain): CustomDomain
    {
        $domain->update([
            'requested_domain' => null,
            'status' => false,
        ]);

        return $domain->fresh(['user']);
    }

    /**
     * Toggle domain status
     *
     * @param CustomDomain $domain
     * @return CustomDomain
     */
    public function toggleStatus(CustomDomain $domain): CustomDomain
    {
        $domain->update([
            'status' => !$domain->status,
        ]);

        return $domain->fresh(['user']);
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

