<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Repositories\LeadRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Lead Service
 * 
 * Business logic for managing leads
 */
class LeadService extends BaseService
{
    /**
     * @var LeadRepositoryInterface
     */
    protected $leadRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * LeadService constructor.
     *
     * @param LeadRepositoryInterface $leadRepository
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        LeadRepositoryInterface $leadRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->leadRepository = $leadRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get paginated leads with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getLeads(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->leadRepository->searchAndPaginate($filters, $perPage);
    }

    /**
     * Get lead by UUID
     *
     * @param string $uuid
     * @return Lead
     * @throws ResourceNotFoundException
     */
    public function getLeadByUuid(string $uuid): Lead
    {
        $lead = $this->leadRepository->findByUuid($uuid);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        $lead->load(['stage', 'assignedAdmin', 'convertedUser', 'activities.admin']);

        return $lead;
    }

    /**
     * Create a new lead
     *
     * @param array $data
     * @return Lead
     */
    public function createLead(array $data): Lead
    {
        return $this->transaction(function () use ($data) {
            $lead = $this->leadRepository->create($data);
            
            return $lead->load(['stage', 'assignedAdmin']);
        });
    }

    /**
     * Update existing lead
     *
     * @param string $uuid
     * @param array $data
     * @return Lead
     * @throws ResourceNotFoundException
     */
    public function updateLead(string $uuid, array $data): Lead
    {
        $lead = $this->leadRepository->findByUuid($uuid);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead, $data) {
            $updated = $this->leadRepository->update($lead, $data);
            
            return $updated->load(['stage', 'assignedAdmin']);
        });
    }

    /**
     * Delete a lead
     *
     * @param string $uuid
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteLead(string $uuid): bool
    {
        $lead = $this->leadRepository->findByUuid($uuid);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead) {
            return $this->leadRepository->delete($lead);
        });
    }

    /**
     * Move lead to different stage
     *
     * @param string $uuid
     * @param int $stageId
     * @param string|null $status
     * @return Lead
     * @throws ResourceNotFoundException
     */
    public function moveToStage(string $uuid, int $stageId, ?string $status = null): Lead
    {
        $lead = $this->leadRepository->findByUuid($uuid);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead, $stageId, $status) {
            return $this->leadRepository->moveToStage($lead, $stageId, $status);
        });
    }

    /**
     * Convert lead to user/tenant
     *
     * @param string $leadUuid
     * @param string $userUuid
     * @param string|null $notes
     * @return Lead
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function convertLead(string $leadUuid, string $userUuid, ?string $notes = null): Lead
    {
        $lead = $this->leadRepository->findByUuid($leadUuid);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        if ($lead->status === 'converted') {
            throw new BusinessLogicException('Lead is already converted');
        }

        $user = $this->userRepository->findByUuid($userUuid);

        if (!$user) {
            throw new ResourceNotFoundException('User not found');
        }

        return $this->transaction(function () use ($lead, $user, $notes) {
            return $this->leadRepository->convertLead($lead, $user->id, $notes);
        });
    }

    /**
     * Get leads by status
     *
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLeadsByStatus(string $status)
    {
        return $this->leadRepository->getByStatus($status);
    }

    /**
     * Get leads assigned to admin
     *
     * @param int $adminId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLeadsAssignedTo(int $adminId)
    {
        return $this->leadRepository->getAssignedTo($adminId);
    }

    /**
     * Get CRM overview/dashboard data
     *
     * @return array
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

