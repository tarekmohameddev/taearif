<?php

namespace App\Domain\Crm\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Crm\Models\LeadActivity;

/**
 * Lead Activity Repository Interface
 * 
 * Defines the contract for LeadActivity data access operations
 */
interface LeadActivityRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get activities for a specific lead
     *
     * @param int $leadId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByLead(int $leadId);

    /**
     * Get activities by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type);

    /**
     * Get pending activities
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPending();

    /**
     * Get completed activities
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompleted();

    /**
     * Mark activity as completed
     *
     * @param LeadActivity $activity
     * @return LeadActivity
     */
    public function markAsCompleted(LeadActivity $activity): LeadActivity;
}

