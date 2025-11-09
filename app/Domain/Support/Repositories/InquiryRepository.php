<?php

namespace App\Domain\Support\Repositories;

use App\Domain\Support\Models\Inquiry;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Inquiry Repository
 *
 * Handles data access for Inquiry model
 */
class InquiryRepository extends BaseRepository implements InquiryRepositoryInterface
{
    /**
     * InquiryRepository constructor.
     *
     * @param Inquiry $model
     */
    public function __construct(Inquiry $model)
    {
        parent::__construct($model);
    }

    /**
     * Get inquiries by status
     * Note: Status field may not exist in table, this is a placeholder
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        // If status column exists, use it; otherwise return all
        if (\Schema::hasColumn($this->model->getTable(), 'status')) {
            return $this->model->where('status', $status)->get();
        }
        return $this->model->all();
    }

    /**
     * Get open inquiries count
     *
     * @return int
     */
    public function getOpenCount(): int
    {
        if (\Schema::hasColumn($this->model->getTable(), 'status')) {
            return $this->model->where('status', 'open')->count();
        }
        return $this->model->count();
    }

    /**
     * Get inquiries assigned to employee
     * Note: assigned_to field may not exist in table
     *
     * @param int $employeeId
     * @return Collection
     */
    public function getAssignedTo(int $employeeId): Collection
    {
        if (\Schema::hasColumn($this->model->getTable(), 'assigned_to')) {
            return $this->model->where('assigned_to', $employeeId)->get();
        }
        return new Collection();
    }

    /**
     * Get inquiries with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInquiries(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'customer']);

        // Search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('customer', function ($customerQuery) use ($search) {
                      $customerQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // User/Tenant filter
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Customer filter
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Inquiry type filter
        if (isset($filters['inquiry_type'])) {
            $query->where('inquiry_type', $filters['inquiry_type']);
        }

        // Property type filter
        if (isset($filters['property_type'])) {
            $query->where('property_type', $filters['property_type']);
        }

        // Budget range filter
        if (isset($filters['min_budget'])) {
            $query->where('budget', '>=', $filters['min_budget']);
        }
        if (isset($filters['max_budget'])) {
            $query->where('budget', '<=', $filters['max_budget']);
        }

        // Location filter
        if (isset($filters['location'])) {
            $query->where('location', 'like', "%{$filters['location']}%");
        }

        // Date range filter
        if (isset($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        // Status filter (if column exists)
        if (isset($filters['status']) && \Schema::hasColumn($this->model->getTable(), 'status')) {
            $query->where('status', $filters['status']);
        }

        // Assigned to filter (if column exists)
        if (isset($filters['assigned_to']) && \Schema::hasColumn($this->model->getTable(), 'assigned_to')) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        // Sorting
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get inquiry by ID with relations
     *
     * @param int $id
     * @param array $relations
     * @return Inquiry|null
     */
    public function getInquiryById(int $id, array $relations = []): ?Inquiry
    {
        return $this->model->with($relations)->find($id);
    }

    /**
     * Get inquiry statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $total = $this->model->count();
        
        $byType = $this->model->selectRaw('inquiry_type, COUNT(*) as count')
            ->groupBy('inquiry_type')
            ->get()
            ->pluck('count', 'inquiry_type')
            ->toArray();

        $byTenant = $this->model->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->with('user:id,first_name,last_name,email')
            ->get()
            ->map(function ($item) {
                return [
                    'user_id' => $item->user_id,
                    'user_name' => $item->user ? $item->user->first_name . ' ' . $item->user->last_name : 'Unknown',
                    'count' => $item->count,
                ];
            });

        $recentCount = $this->model->where('created_at', '>=', now()->subDays(7))->count();
        $todayCount = $this->model->whereDate('created_at', today())->count();

        return [
            'total' => $total,
            'today' => $todayCount,
            'last_7_days' => $recentCount,
            'by_type' => $byType,
            'by_tenant' => $byTenant,
        ];
    }
}

