<?php

namespace App\Domain\Crm\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Crm\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lead Repository Interface
 * 
 * Defines the contract for Lead data access operations
 */
interface LeadRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate leads with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get leads by status
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStatus(string $status);

    /**
     * Get leads by stage
     *
     * @param int $stageId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByStage(int $stageId);

    /**
     * Get leads assigned to admin
     *
     * @param int $adminId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignedTo(int $adminId);

    /**
     * Convert lead to user
     *
     * @param Lead $lead
     * @param int $userId
     * @param string|null $notes
     * @return Lead
     */
    public function convertLead(Lead $lead, int $userId, ?string $notes = null): Lead;

    /**
     * Move lead to different stage
     *
     * @param Lead $lead
     * @param int $stageId
     * @param string|null $status
     * @return Lead
     */
    public function moveToStage(Lead $lead, int $stageId, ?string $status = null): Lead;
}
