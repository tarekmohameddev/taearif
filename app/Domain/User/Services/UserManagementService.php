<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Models\UserActivityLog;
use App\Services\WhatsAppService;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * User Management Service
 *
 * Handles user/tenant management business logic
 */
class UserManagementService extends BaseService
{
    /**
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;
    protected WhatsAppService $whatsappService;

    /**
     * UserManagementService constructor.
     *
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(UserRepositoryInterface $userRepository, WhatsAppService $whatsappService)
    {
        $this->userRepository = $userRepository;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Get all tenants with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllTenants(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->getTenants($filters, $perPage);
    }

    /**
     * Get user by UUID
     *
     * @param string $uuid
     * @return User
     * @throws ResourceNotFoundException
     */
    public function getUserByUuid(string $uuid): User
    {
        $user = $this->userRepository->find($uuid, ['*'], ['referrer', 'activeMembership.package', 'memberships']);

        if (!$user || $user->account_type !== 'tenant') {
            throw new ResourceNotFoundException('User not found');
        }

        return $user;
    }

    /**
     * Create new user
     *
     * @param array $data
     * @return User
     * @throws BusinessLogicException
     */
    public function createUser(array $data): User
    {
        // Check if email already exists
        if ($this->userRepository->findByEmail($data['email'])) {
            throw new BusinessLogicException('Email already exists', 'USER_EMAIL_EXISTS', 400);
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Set account type
        $data['account_type'] = 'tenant';

        // Set default values
        $data['active'] = $data['active'] ?? true;
        $data['status'] = $data['status'] ?? 1;
        $data['email_verified'] = $data['email_verified'] ?? false;

        return $this->transaction(function () use ($data) {
            return $this->userRepository->create($data);
        });
    }

    /**
     * Update user
     *
     * @param string $uuid
     * @param array $data
     * @return User
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateUser(string $uuid, array $data): User
    {
        $user = $this->getUserByUuid($uuid);

        // Check if email is being changed and already exists
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser && $existingUser->uuid !== $uuid) {
                throw new BusinessLogicException('Email already exists', 'USER_EMAIL_EXISTS', 400);
            }
        }

        // Don't allow changing password through update
        unset($data['password']);
        unset($data['account_type']);

        return $this->transaction(function () use ($user, $data) {
            $user->update($data);
            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Delete user
     *
     * @param string $uuid
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteUser(string $uuid): bool
    {
        $user = $this->getUserByUuid($uuid);

        return $this->transaction(function () use ($user) {
            return $user->delete();
        });
    }

    /**
     * Change user password
     *
     * @param string $uuid
     * @param string $newPassword
     * @return User
     * @throws ResourceNotFoundException
     */
    public function changePassword(string $uuid, string $newPassword): User
    {
        $user = $this->getUserByUuid($uuid);

        return $this->transaction(function () use ($user, $newPassword) {
            $user->password = Hash::make($newPassword);
            $user->save();

            // Revoke all existing tokens (logout from all devices)
            $user->tokens()->delete();

            return $user;
        });
    }

    /**
     * Ban/Unban user (toggle active status)
     *
     * @param string $uuid
     * @return User
     * @throws ResourceNotFoundException
     */
    public function toggleBan(string $uuid): User
    {
        $user = $this->getUserByUuid($uuid);

        return $this->transaction(function () use ($user) {
            return $this->userRepository->toggleActive($user);
        });
    }

    /**
     * Toggle featured status
     *
     * @param string $uuid
     * @return User
     * @throws ResourceNotFoundException
     */
    public function toggleFeatured(string $uuid): User
    {
        $user = $this->getUserByUuid($uuid);

        return $this->transaction(function () use ($user) {
            return $this->userRepository->toggleFeatured($user);
        });
    }

    /**
     * Log activity performed on a user account
     */
    public function logUserActivity(
        string $uuid,
        string $action,
        ?string $description = null,
        array $metadata = [],
        ?int $adminId = null
    ): UserActivityLog {
        $user = $this->getUserByUuid($uuid);

        return UserActivityLog::create([
            'user_id' => $user->id,
            'admin_id' => $adminId ?? auth('admin-sanctum')->id(),
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Retrieve paginated activity log for a user account
     */
    public function getActivityLog(string $uuid, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->getUserByUuid($uuid);

        $query = UserActivityLog::with(['admin'])
            ->where('user_id', $user->id);

        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get a paginated list of invoices/subscriptions for a user
     */
    public function getUserInvoices(string $uuid, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->getUserByUuid($uuid);

        $query = Invoice::with(['package'])
            ->where('user_id', $user->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['plan_id'])) {
            $query->where('package_id', $filters['plan_id']);
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Send WhatsApp message to user
     */
    public function sendWhatsAppMessage(
        string $uuid,
        string $message,
        ?string $templateName = null,
        array $templateVariables = []
    ): array {
        $user = $this->getUserByUuid($uuid);

        if (empty($user->phone)) {
            throw new BusinessLogicException('User does not have a phone number on file', 'USER_NO_PHONE', 400);
        }

        try {
            if ($templateName) {
                $result = $this->whatsappService->sendTemplateToPhone(
                    $user->phone,
                    $templateName,
                    config('services.meta.template_language', 'ar'),
                    $templateVariables
                );

                if (!$result['success']) {
                    throw new \RuntimeException($result['message'] ?? 'Failed to send WhatsApp template message');
                }
            } else {
                $sent = $this->whatsappService->sendMessage($user->phone, $message);
                if (!$sent) {
                    throw new \RuntimeException('WhatsApp service returned failure response');
                }
            }
        } catch (\Throwable $e) {
            throw new BusinessLogicException(
                'Failed to send WhatsApp message: ' . $e->getMessage(),
                'WHATSAPP_SEND_FAILED',
                500
            );
        }

        $this->logUserActivity(
            $user->uuid,
            'whatsapp_sent',
            "WhatsApp message sent to {$user->phone}",
            [
                'template' => $templateName,
                'template_variables' => $templateVariables,
                'message_preview' => mb_strimwidth($message, 0, 120, '...'),
            ]
        );

        return [
            'phone' => $user->phone,
            'template' => $templateName,
            'sent_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Pause/suspend user account
     */
    public function pauseUser(string $uuid, string $reason, ?string $adminNotes = null): User
    {
        $user = $this->getUserByUuid($uuid);

        return $this->transaction(function () use ($user, $reason, $adminNotes) {
            if (!$user->active) {
                throw new BusinessLogicException('User is already paused', 'USER_ALREADY_PAUSED', 400);
            }

            $user->active = false;
            $user->save();

            // Revoke all tokens so the user can't access while paused
            $user->tokens()->delete();

            $this->logUserActivity(
                $user->uuid,
                'user_paused',
                "User paused: {$reason}",
                [
                    'reason' => $reason,
                    'admin_notes' => $adminNotes,
                ]
            );

            return $user->fresh();
        });
    }

    /**
     * Resume paused user account
     */
    public function resumeUser(string $uuid): User
    {
        $user = $this->getUserByUuid($uuid);

        return $this->transaction(function () use ($user) {
            if ($user->active) {
                throw new BusinessLogicException('User is already active', 'USER_ALREADY_ACTIVE', 400);
            }

            $user->active = true;
            $user->save();

            $this->logUserActivity(
                $user->uuid,
                'user_resumed',
                'User account resumed',
                []
            );

            return $user->fresh();
        });
    }

    /**
     * Change user's subscription plan
     */
    public function changePlan(
        string $uuid,
        int $newPlanId,
        string $changeType,
        ?string $adminNotes = null
    ): User {
        $user = $this->getUserByUuid($uuid);

        $newPlan = Plan::active()->find($newPlanId);
        if (!$newPlan) {
            throw new BusinessLogicException('Selected plan is not available', 'PLAN_NOT_AVAILABLE', 404);
        }

        $currentSubscription = $user->activeMembership;
        if (!$currentSubscription) {
            throw new BusinessLogicException('User does not have an active subscription', 'NO_ACTIVE_SUBSCRIPTION', 400);
        }

        if ($currentSubscription->package_id === $newPlan->id) {
            throw new BusinessLogicException('User is already on this plan', 'PLAN_ALREADY_APPLIED', 400);
        }

        return $this->transaction(function () use ($user, $newPlan, $currentSubscription, $changeType, $adminNotes) {
            $previousPlan = $currentSubscription->package;

            if ($changeType === 'immediate') {
                $currentSubscription->expire_date = now()->toDateString();
                $currentSubscription->status = 1;
                $currentSubscription->save();

                Invoice::create([
                    'user_id' => $user->id,
                    'package_id' => $newPlan->id,
                    'package_price' => $newPlan->price,
                    'price' => $newPlan->price,
                    'currency' => $currentSubscription->currency ?? 'SAR',
                    'currency_symbol' => $currentSubscription->currency_symbol ?? 'ر.س',
                    'payment_method' => 'admin_change',
                    'transaction_id' => 'ADMIN_PLAN_CHANGE_' . now()->timestamp,
                    'status' => 1,
                    'is_trial' => false,
                    'start_date' => now()->toDateString(),
                    'expire_date' => now()->addDays($newPlan->term ?? 30)->toDateString(),
                ]);
            } else {
                $currentSubscription->metadata = array_merge($currentSubscription->metadata ?? [], [
                    'scheduled_plan_change' => [
                        'new_plan_id' => $newPlan->id,
                        'scheduled_at' => now()->toIso8601String(),
                        'admin_notes' => $adminNotes,
                    ],
                ]);
                $currentSubscription->save();
            }

            $this->logUserActivity(
                $user->uuid,
                'plan_changed',
                sprintf(
                    'Plan change from %s to %s (%s)',
                    $previousPlan?->title ?? 'Unknown',
                    $newPlan->title,
                    $changeType
                ),
                [
                    'old_plan_id' => $previousPlan?->id,
                    'new_plan_id' => $newPlan->id,
                    'change_type' => $changeType,
                    'admin_notes' => $adminNotes,
                ]
            );

            return $user->fresh(['activeMembership.package']);
        });
    }

    /**
     * Cancel user's active subscription
     */
    public function cancelSubscription(
        string $uuid,
        string $cancelType,
        string $reason,
        ?string $adminNotes = null
    ): User {
        $user = $this->getUserByUuid($uuid);

        $subscription = $user->activeMembership;
        if (!$subscription) {
            throw new BusinessLogicException('User has no active subscription', 'NO_ACTIVE_SUBSCRIPTION', 400);
        }

        return $this->transaction(function () use ($user, $subscription, $cancelType, $reason, $adminNotes) {
            $originalExpireDate = $subscription->expire_date;

            if ($cancelType === 'immediate') {
                $subscription->expire_date = now()->toDateString();
                $subscription->status = 0;
                $subscription->save();
            } else {
                $subscription->metadata = array_merge($subscription->metadata ?? [], [
                    'scheduled_cancellation' => [
                        'reason' => $reason,
                        'scheduled_at' => now()->toIso8601String(),
                        'admin_notes' => $adminNotes,
                    ],
                ]);
                $subscription->save();
            }

            $this->logUserActivity(
                $user->uuid,
                'subscription_cancelled',
                "Subscription cancelled ({$cancelType}): {$reason}",
                [
                    'subscription_id' => $subscription->id,
                    'plan_name' => $subscription->package?->title,
                    'cancel_type' => $cancelType,
                    'reason' => $reason,
                    'admin_notes' => $adminNotes,
                    'original_expire_date' => $originalExpireDate,
                ]
            );

            return $user->fresh(['activeMembership.package']);
        });
    }
}

