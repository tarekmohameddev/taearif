<?php

namespace App\Domain\Crm\Services;

use App\Domain\Crm\Models\LeadActivity;
use App\Domain\Crm\Repositories\LeadActivityRepositoryInterface;
use App\Domain\Crm\Repositories\LeadRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;

/**
 * Lead Activity Service
 * 
 * Business logic for managing lead activities
 */
class LeadActivityService extends BaseService
{
    /**
     * @var LeadActivityRepositoryInterface
     */
    protected $activityRepository;

    /**
     * @var LeadRepositoryInterface
     */
    protected $leadRepository;

    /**
     * LeadActivityService constructor.
     *
     * @param LeadActivityRepositoryInterface $activityRepository
     * @param LeadRepositoryInterface $leadRepository
     */
    public function __construct(
        LeadActivityRepositoryInterface $activityRepository,
        LeadRepositoryInterface $leadRepository
    ) {
        $this->activityRepository = $activityRepository;
        $this->leadRepository = $leadRepository;
    }

    /**
     * Get activities for a lead
     *
     * @param int $leadId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ResourceNotFoundException
     */
    public function getLeadActivities(int $leadId)
    {
        $lead = $this->leadRepository->findById($leadId);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->activityRepository->getByLead($lead->id);
    }

    /**
     * Create a new activity for a lead
     *
     * @param int $leadId
     * @param array $data
     * @return LeadActivity
     * @throws ResourceNotFoundException
     */
    public function createActivity(int $leadId, array $data): LeadActivity
    {
        $lead = $this->leadRepository->findById($leadId);

        if (!$lead) {
            throw new ResourceNotFoundException('Lead not found');
        }

        return $this->transaction(function () use ($lead, $data) {
            $data['lead_id'] = $lead->id;
            
            $activity = $this->activityRepository->create($data);
            
            return $activity->load(['lead', 'admin']);
        });
    }

    /**
     * Update an activity
     *
     * @param int $activityId
     * @param array $data
     * @return LeadActivity
     * @throws ResourceNotFoundException
     */
    public function updateActivity(int $activityId, array $data): LeadActivity
    {
        $activity = $this->activityRepository->findById($activityId);

        if (!$activity) {
            throw new ResourceNotFoundException('Activity not found');
        }

        return $this->transaction(function () use ($activity, $data) {
            $updated = $this->activityRepository->update($activity, $data);
            
            return $updated->load(['lead', 'admin']);
        });
    }

    /**
     * Delete an activity
     *
     * @param int $activityId
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteActivity(int $activityId): bool
    {
        $activity = $this->activityRepository->findById($activityId);

        if (!$activity) {
            throw new ResourceNotFoundException('Activity not found');
        }

        return $this->transaction(function () use ($activity) {
            return $this->activityRepository->delete($activity);
        });
    }

    /**
     * Mark activity as completed
     *
     * @param int $activityId
     * @return LeadActivity
     * @throws ResourceNotFoundException
     */
    public function completeActivity(int $activityId): LeadActivity
    {
        $activity = $this->activityRepository->findById($activityId);

        if (!$activity) {
            throw new ResourceNotFoundException('Activity not found');
        }

        return $this->transaction(function () use ($activity) {
            return $this->activityRepository->markAsCompleted($activity);
        });
    }

    /**
     * Get pending activities
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingActivities()
    {
        return $this->activityRepository->getPending();
    }

    /**
     * Get completed activities
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompletedActivities()
    {
        return $this->activityRepository->getCompleted();
    }
}

