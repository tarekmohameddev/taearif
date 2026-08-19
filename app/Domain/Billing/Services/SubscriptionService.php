<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Subscription;
use App\Domain\Billing\Repositories\SubscriptionRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

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
     * Get latest subscription for a user.
     *
     * @param int $userId
     * @return Subscription|null
     */
    public function getLatestSubscriptionForUser(int $userId): ?Subscription
    {
        return $this->subscriptionRepository->getLatestForUser($userId);
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

    /**
     * Placeholder for upcoming change-plan functionality.
     *
     * @param int $subscriptionId
     * @param array $data
     * @return Subscription
     */
    public function changePlan(int $subscriptionId, array $data = []): Subscription
    {
        $subscription = $this->getSubscriptionById($subscriptionId);
        $subscription->loadMissing('user');

        $latest = $this->subscriptionRepository->getLatestForUser($subscription->user_id);

        if (!$latest || $latest->id !== $subscription->id) {
            throw new BusinessLogicException(
                'Only the latest subscription can be changed.',
                'NOT_LATEST_SUBSCRIPTION',
                409
            );
        }

        $plan = Plan::query()
            ->where('id', $data['plan_id'])
            ->where(function ($q) {
                $q->where('status', 1)
                  ->orWhere('is_active', 1);
            })
            ->first();
        if (!$plan) {
            throw new ResourceNotFoundException(sprintf(
                'Selected plan (%d) is not available.',
                $data['plan_id']
            ));
        }

        if ((int) $subscription->package_id === (int) $plan->id) {
            throw new BusinessLogicException(
                'Subscription is already on the selected plan.',
                'PLAN_ALREADY_ACTIVE',
                422
            );
        }

        $startDate = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfDay()
            : now()->startOfDay();

        $isFuture = $startDate->greaterThan(now()->startOfDay());

        return $this->executeInTransaction(function () use (
            $subscription,
            $plan,
            $startDate,
            $isFuture,
            $data
        ) {
            $current = $subscription->fresh();

            if ($isFuture) {
                // Keep current membership active until the day before the new start date.
                $current->expire_date = $startDate->copy()->subDay()->toDateString();
                $current->save();
            } else {
                // Immediate change: end the current membership today.
                $current->expire_date = now()->toDateString();
                $current->status = 0;
                $current->save();
            }

            $newSubscription = Subscription::create([
                'user_id' => $current->user_id,
                'package_id' => $plan->id,
                'package_price' => $plan->price,
                'price' => $plan->price,
                'currency' => $current->currency ?? 'SAR',
                'currency_symbol' => $current->currency_symbol ?? 'SAR',
                'payment_method' => $data['payment_method'],
                'transaction_id' => 'ADMIN_PLAN_CHANGE_' . Str::uuid(),
                'status' => 1,
                'is_trial' => $plan->term === 'trial' || $plan->hasTrial(),
                'trial_days' => ($plan->term === 'trial' || $plan->hasTrial())
                    ? (((int) $plan->trial_days > 0) ? (int) $plan->trial_days : \App\Services\MembershipService::DEFAULT_TRIAL_DAYS)
                    : 0,
                'start_date' => $startDate->toDateString(),
                'expire_date' => $this->calculateExpireDate($startDate, $plan->term, $plan->trial_days)->toDateString(),
                'transaction_details' => !empty($data['notes'])
                    ? json_encode(['admin_notes' => $data['notes']])
                    : null,
            ]);

            return $newSubscription->load(['user', 'package', 'latestInvoice']);
        });
    }

    protected function calculateExpireDate(Carbon $start, ?string $term, $trialDays = null): Carbon
    {
        if ($term === 'trial') {
            $days = (int) $trialDays;
            if ($days < 1) {
                $days = \App\Services\MembershipService::DEFAULT_TRIAL_DAYS;
            }

            return $start->copy()->addDays($days);
        }

        return match ($term) {
            'daily' => $start->copy()->addDay(),
            'weekly' => $start->copy()->addWeek(),
            'monthly' => $start->copy()->addMonth(),
            'yearly' => $start->copy()->addYear(),
            default => $start->copy()->addMonth(),
        };
    }
}

