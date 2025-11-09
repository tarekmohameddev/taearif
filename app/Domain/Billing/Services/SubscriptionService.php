<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Subscription Service
 *
 * Handles subscription viewing and listing (read-only)
 */
class SubscriptionService extends BaseService
{
    /**
     * @var SubscriptionRepositoryInterface
     */
    protected SubscriptionRepositoryInterface $subscriptionRepository;

    /**
     * SubscriptionService constructor.
     *
     * @param SubscriptionRepositoryInterface $subscriptionRepository
     */
    public function __construct(SubscriptionRepositoryInterface $subscriptionRepository)
    {
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Get all subscriptions with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllSubscriptions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->subscriptionRepository->getSubscriptions($filters, $perPage);
    }

    /**
     * Get subscription by ID
     *
     * @param int $id
     * @return Subscription
     * @throws ResourceNotFoundException
     */
    public function getSubscriptionById(int $id): Subscription
    {
        $subscription = $this->subscriptionRepository->findById($id);

        if (!$subscription) {
            throw new ResourceNotFoundException('Subscription not found');
        }

        return $subscription->load(['user', 'package']);
    }

    /**
     * Get subscription statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'active' => $this->subscriptionRepository->getActiveCount(),
            'expiring_soon' => $this->subscriptionRepository->getExpiring(7)->count(),
            'not_renewed' => $this->subscriptionRepository->getNotRenewed(30)->count(),
            'trial_not_upgraded' => $this->subscriptionRepository->getTrialNotUpgraded()->count(),
        ];
    }
}

