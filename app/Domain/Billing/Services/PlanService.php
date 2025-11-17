<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Repositories\PlanRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Plan Service
 *
 * Handles plan/package management business logic
 */
class PlanService extends BaseService
{
    /**
     * @var PlanRepositoryInterface
     */
    protected PlanRepositoryInterface $planRepository;

    /**
     * PlanService constructor.
     *
     * @param PlanRepositoryInterface $planRepository
     */
    public function __construct(PlanRepositoryInterface $planRepository)
    {
        $this->planRepository = $planRepository;
    }

    /**
     * Get all plans with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPlans(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->planRepository->getPlans($filters, $perPage);
    }

    /**
     * Get plan by ID
     *
     * @param int $id
     * @return Plan
     * @throws ResourceNotFoundException
     */
    public function getPlanById(int $id): Plan
    {
        $plan = $this->planRepository->findById($id);

        if (!$plan) {
            throw new ResourceNotFoundException('Plan not found');
        }

        return $this->loadActiveSubscribersCount($plan);
    }

    /**
     * Get plan by slug
     *
     * @param string $slug
     * @return Plan
     * @throws ResourceNotFoundException
     */
    public function getPlanBySlug(string $slug): Plan
    {
        $plan = $this->planRepository->findBySlug($slug);

        if (!$plan) {
            throw new ResourceNotFoundException('Plan not found');
        }

        return $plan;
    }

    /**
     * Create new plan
     *
     * @param array $data
     * @return Plan
     * @throws BusinessLogicException
     */
    public function createPlan(array $data): Plan
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        // Check if slug already exists
        if ($this->planRepository->findBySlug($data['slug'])) {
            throw new BusinessLogicException('Slug already exists', 'PLAN_SLUG_EXISTS', 400);
        }

        // Set defaults
        $data['is_active'] = $data['is_active'] ?? true;
        $data['status'] = $data['status'] ?? 1;
        $data['is_trial'] = $data['is_trial'] ?? false;
        $data['trial_days'] = $data['trial_days'] ?? 0;
        $data['featured'] = $data['featured'] ?? 0;

        $data = $this->normalizePlanFlags($data);

        $plan = $this->executeInTransaction(function () use ($data) {
            return $this->planRepository->create($data);
        });

        return $this->loadActiveSubscribersCount($plan);
    }

    /**
     * Update plan
     *
     * @param int $id
     * @param array $data
     * @return Plan
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updatePlan(int $id, array $data): Plan
    {
        $plan = $this->getPlanById($id);

        // Check if slug is being changed and already exists
        if (isset($data['slug']) && $data['slug'] !== $plan->slug) {
            $existingPlan = $this->planRepository->findBySlug($data['slug']);
            if ($existingPlan && $existingPlan->id !== $id) {
                throw new BusinessLogicException('Slug already exists', 'PLAN_SLUG_EXISTS', 400);
            }
        }

        $data = $this->normalizePlanFlags($data);

        $plan = $this->executeInTransaction(function () use ($plan, $data) {
            $plan->update($data);
            return $plan->fresh();
        });

        return $this->loadActiveSubscribersCount($plan);
    }

    /**
     * Delete plan
     *
     * @param int $id
     * @return Plan
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function deletePlan(int $id): Plan
    {
        $plan = $this->getPlanById($id);

        // Check if plan has active subscriptions
        $hasActiveSubscription = $plan->memberships()
            ->where('status', 1)
            ->whereDate('expire_date', '>=', now()->toDateString())
            ->exists();

        if ($hasActiveSubscription) {
            throw new BusinessLogicException(
                'Cannot delete plan with active subscriptions',
                'PLAN_HAS_SUBSCRIPTIONS',
                400
            );
        }

        $this->executeInTransaction(function () use ($plan) {
            $plan->delete();
        });

        return $plan;
    }

    /**
     * Toggle plan active status
     *
     * @param int $id
     * @return Plan
     * @throws ResourceNotFoundException
     */
    public function toggleActive(int $id): Plan
    {
        $plan = $this->getPlanById($id);

        $plan = $this->executeInTransaction(function () use ($plan) {
            return $this->planRepository->toggleActive($plan);
        });

        return $this->loadActiveSubscribersCount($plan);
    }

    /**
     * Toggle plan featured status
     *
     * @param int $id
     * @return Plan
     * @throws ResourceNotFoundException
     */
    public function toggleFeatured(int $id): Plan
    {
        $plan = $this->getPlanById($id);

        $plan = $this->executeInTransaction(function () use ($plan) {
            return $this->planRepository->toggleFeatured($plan);
        });

        return $this->loadActiveSubscribersCount($plan);
    }

    /**
     * Normalize enum-backed boolean flags before persisting.
     *
     * @param array $data
     * @return array
     */
    protected function normalizePlanFlags(array $data): array
    {
        foreach (['featured', 'is_trial', 'status', 'is_active'] as $flag) {
            if (array_key_exists($flag, $data)) {
                $data[$flag] = $this->normalizeBooleanFlag($data[$flag]);
            }
        }

        return $data;
    }

    /**
     * Convert mixed boolean input to the string values expected by legacy enums.
     *
     * @param mixed $value
     * @return string
     */
    protected function normalizeBooleanFlag(mixed $value): string
    {
        if (is_string($value)) {
            $value = strtolower($value);
            if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
                return '1';
            }
            if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
                return '0';
            }
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1 ? '1' : '0';
        }

        return '0';
    }

    /**
     * Load active subscribers count for the given plan.
     */
    protected function loadActiveSubscribersCount(Plan $plan): Plan
    {
        return $plan->loadCount([
            'memberships as active_memberships_count' => function ($membershipQuery) {
                $membershipQuery->where('status', 1)
                    ->whereDate('expire_date', '>=', now()->toDateString());
            },
        ]);
    }
}

