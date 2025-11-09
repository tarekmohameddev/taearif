<?php

namespace App\Domain\Crm\Repositories;

use App\Domain\Crm\Models\Lead;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lead Repository
 * 
 * Handles data access for Lead model
 */
class LeadRepository extends BaseRepository implements LeadRepositoryInterface
{
    /**
     * LeadRepository constructor.
     *
     * @param Lead $model
     */
    public function __construct(Lead $model)
    {
        parent::__construct($model);
    }

    /**
     * Search and paginate leads with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->with(['stage', 'assignedAdmin', 'convertedUser']);

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        // Source filter
        if (!empty($filters['source'])) {
            $query->bySource($filters['source']);
        }

        // Stage filter
        if (!empty($filters['stage_id'])) {
            $query->byStage($filters['stage_id']);
        }

        // Assigned admin filter
        if (!empty($filters['assigned_admin_id'])) {
            $query->assignedTo($filters['assigned_admin_id']);
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
     * Get leads by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model
            ->byStatus($status)
            ->with(['stage', 'assignedAdmin'])
            ->get();
    }

    /**
     * Get leads by stage
     *
     * @param int $stageId
     * @return Collection
     */
    public function getByStage(int $stageId): Collection
    {
        return $this->model
            ->byStage($stageId)
            ->with(['assignedAdmin'])
            ->get();
    }

    /**
     * Get leads assigned to admin
     *
     * @param int $adminId
     * @return Collection
     */
    public function getAssignedTo(int $adminId): Collection
    {
        return $this->model
            ->assignedTo($adminId)
            ->with(['stage'])
            ->get();
    }

    /**
     * Convert lead to user
     *
     * @param Lead $lead
     * @param int $userId
     * @param string|null $notes
     * @return Lead
     */
    public function convertLead(Lead $lead, int $userId, ?string $notes = null): Lead
    {
        $lead->update([
            'status' => 'converted',
            'converted_user_id' => $userId,
            'converted_at' => now(),
            'notes' => $notes ? ($lead->notes ? $lead->notes . "\n\n" . $notes : $notes) : $lead->notes,
        ]);

        return $lead->fresh(['convertedUser', 'stage', 'assignedAdmin']);
    }

    /**
     * Move lead to different stage
     *
     * @param Lead $lead
     * @param int $stageId
     * @param string|null $status
     * @return Lead
     */
    public function moveToStage(Lead $lead, int $stageId, ?string $status = null): Lead
    {
        $updateData = ['stage_id' => $stageId];
        
        if ($status) {
            $updateData['status'] = $status;
        }

        $lead->update($updateData);

        return $lead->fresh(['stage', 'assignedAdmin']);
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

