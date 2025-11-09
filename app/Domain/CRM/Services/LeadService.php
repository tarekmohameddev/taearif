<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Repositories\LeadRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lead Service
 *
 * Business logic for managing leads
 */
class LeadService extends BaseService
{
    protected LeadRepositoryInterface $leadRepository;
    protected UserRepositoryInterface $userRepository;

    public function __construct(
        LeadRepositoryInterface $leadRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->leadRepository = $leadRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get paginated leads with filters.
     */
    public function getLeads(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->leadRepository->searchAndPaginate($filters, $perPage);
    }

    /**
     * Retrieve a lead by its numeric ID.
     *
     * @throws ResourceNotFoundException
     */
    public function getLeadById(int $id): Lead
    {
        $lead = $this->leadRepository->findById($id);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        $lead->load(['stage', 'assignedAdmin', 'convertedUser', 'activities.admin']);

        return $lead;
    }

    /**
     * Create a new lead.
     */
    public function createLead(array $data): Lead
    {
        return $this->transaction(function () use ($data) {
            $lead = $this->leadRepository->create($data);

            return $lead->load(['stage', 'assignedAdmin']);
        });
    }

    /**
     * Update an existing lead.
     *
     * @throws ResourceNotFoundException
     */
    public function updateLead(int $id, array $data): Lead
    {
        $lead = $this->leadRepository->findById($id);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead, $data) {
            $updated = $this->leadRepository->update($lead, $data);

            return $updated->load(['stage', 'assignedAdmin']);
        });
    }

    /**
     * Delete a lead.
     *
     * @throws ResourceNotFoundException
     */
    public function deleteLead(int $id): bool
    {
        $lead = $this->leadRepository->findById($id);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead) {
            return $this->leadRepository->delete($lead);
        });
    }

    /**
     * Move a lead to a different stage.
     *
     * @throws ResourceNotFoundException
     */
    public function moveToStage(int $leadId, int $stageId, ?string $status = null): Lead
    {
        $lead = $this->leadRepository->findById($leadId);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead, $stageId, $status) {
            return $this->leadRepository->moveToStage($lead, $stageId, $status);
        });
    }

    /**
     * Convert a lead to an existing user/tenant.
     *
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function convertLead(int $leadId, int $userId, ?string $notes = null): Lead
    {
        $lead = $this->leadRepository->findById($leadId);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        if ($lead->status === 'converted') {
            throw new BusinessLogicException('Lead is already converted');
        }

        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new ResourceNotFoundException('User not found');
        }

        return $this->transaction(function () use ($lead, $user, $notes) {
            return $this->leadRepository->convertLead($lead, $user->id, $notes);
        });
    }

    /**
     * Get leads by status.
     */
    public function getLeadsByStatus(string $status)
    {
        return $this->leadRepository->getByStatus($status);
    }

    /**
     * Get leads assigned to a specific admin.
     */
    public function getLeadsAssignedTo(int $adminId)
    {
        return $this->leadRepository->getAssignedTo($adminId);
    }

    /**
     * Retrieve CRM overview metrics.
     */
    public function getCrmOverview(): array
    {
        return [
            'total_leads' => $this->leadRepository->count(),
            'active_leads' => Lead::active()->count(),
            'converted_leads' => Lead::converted()->count(),
            'lost_leads' => Lead::byStatus('lost')->count(),
            'leads_by_status' => [
                'new' => Lead::byStatus('new')->count(),
                'contacted' => Lead::byStatus('contacted')->count(),
                'qualified' => Lead::byStatus('qualified')->count(),
                'converted' => Lead::byStatus('converted')->count(),
                'lost' => Lead::byStatus('lost')->count(),
            ],
            'leads_by_source' => [
                'website' => Lead::bySource('website')->count(),
                'referral' => Lead::bySource('referral')->count(),
                'ads' => Lead::bySource('ads')->count(),
                'manual' => Lead::bySource('manual')->count(),
                'other' => Lead::bySource('other')->count(),
            ],
            'recent_leads' => Lead::with(['stage', 'assignedAdmin'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}

