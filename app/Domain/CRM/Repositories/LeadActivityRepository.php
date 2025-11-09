<?php

namespace App\Domain\Crm\Repositories;

use App\Domain\Crm\Models\LeadActivity;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;

/**
 * Lead Activity Repository
 * 
 * Handles data access for LeadActivity model
 */
class LeadActivityRepository extends BaseRepository implements LeadActivityRepositoryInterface
{
    /**
     * LeadActivityRepository constructor.
     *
     * @param LeadActivity $model
     */
    public function __construct(LeadActivity $model)
    {
        parent::__construct($model);
    }

    /**
     * Get activities for a specific lead
     *
     * @param int $leadId
     * @return Collection
     */
    public function getByLead(int $leadId): Collection
    {
        return $this->model
            ->where('lead_id', $leadId)
            ->with(['admin'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get activities by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->model
            ->byType($type)
            ->with(['lead', 'admin'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get pending activities
     *
     * @return Collection
     */
    public function getPending(): Collection
    {
        return $this->model
            ->pending()
            ->with(['lead', 'admin'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * Get completed activities
     *
     * @return Collection
     */
    public function getCompleted(): Collection
    {
        return $this->model
            ->completed()
            ->with(['lead', 'admin'])
            ->orderBy('completed_at', 'desc')
            ->get();
    }

    /**
     * Mark activity as completed
     *
     * @param LeadActivity $activity
     * @return LeadActivity
     */
    public function markAsCompleted(LeadActivity $activity): LeadActivity
    {
        $activity->markAsCompleted();
        return $activity->fresh(['lead', 'admin']);
    }
}

