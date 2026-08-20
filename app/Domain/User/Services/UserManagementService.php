<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Models\UserActivityLog;
use App\Models\Api\GeneralSetting;
use App\Models\PropertyRequestStatus;
use App\Models\PasswordResetLog;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Models\Invoice;
use App\Models\Package;
use App\Models\User as TenantUser;
use App\Services\MembershipService;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use App\Jobs\SyncTenantToPipedriveJob;
use App\Services\TenantCrmBootstrapService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
     * List tenant users with normalized filters.
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function listUsers(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $normalizedFilters = $this->normalizeListFilters($filters);
        $perPage = $perPage > 0 ? min($perPage, 100) : 20;

        return $this->userRepository->searchAndPaginate($normalizedFilters, $perPage);
    }

    /**
     * Get user by ID
     *
     * @param int $id
     * @return User
     * @throws ResourceNotFoundException
     */
    public function getUserById(int $id): User
    {
        $user = $this->userRepository->findByIdWith(
            $id,
            ['referrer', 'activeMembership.package', 'memberships']
        );

        $user = $this->ensureFound($user, 'User not found');

        if ($user->account_type !== 'tenant') {
            throw new ResourceNotFoundException('User not found');
        }

        return $user;
    }

    /**
     * Retrieve user details by ID (API alias).
     *
     * @param int $id
     * @return User
     * @throws ResourceNotFoundException
     */
    public function getUser(int $id): User
    {
        return $this->getUserById($id);
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
            $this->fail('Email already exists', 'USER_EMAIL_EXISTS', 400);
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Set account type
        $data['account_type'] = 'tenant';

        // Set default values
        $data['active'] = $data['active'] ?? true;
        $data['status'] = $data['status'] ?? 1;
        $data['email_verified'] = $data['email_verified'] ?? false;

        return $this->executeInTransaction(function () use ($data) {
            // Normalize city/district (accept id or name)
            $cityId = $this->resolveCityId($data['city'] ?? null);
            if ($cityId !== null) {
                $data['city'] = (string) $cityId;
            } else {
                unset($data['city']); // don't store invalid value
            }
            if (array_key_exists('district', $data)) {
                $districtId = $this->resolveDistrictId($data['district'], $cityId);
                if ($districtId !== null) {
                    $data['state'] = (string) $districtId; // state column used for district id
                }
                unset($data['district']);
            }
            $user = $this->userRepository->create($data);

            // Ensure api_general_settings has baseline record for this tenant
            $siteName = $data['company_name'] ?? ($data['first_name'] ?? 'Tenant');
            GeneralSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'site_name' => $siteName,
                    // Insert default assets if not provided
                    'logo' => 'logo.png',
                    'favicon' => 'favicon.png',
                ]
            );

            PropertyRequestStatus::ensureWorkflowStatusesForTenant((int) $user->id);

            app(TenantCrmBootstrapService::class)->ensureForTenantSafely((int) $user->id);

            SyncTenantToPipedriveJob::dispatch($user->id, 'registration');

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Update user
     *
     * @param int $id
     * @param array $data
     * @return User
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateUser(int $id, array $data): User
    {
        $user = $this->getUserById($id);

        // Check if email is being changed and already exists
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $existingUser = $this->userRepository->findByEmail($data['email']);
            if ($existingUser && $existingUser->id !== $user->id) {
                $this->fail('Email already exists', 'USER_EMAIL_EXISTS', 400);
            }
        }

        // Don't allow changing password through update
        unset($data['password']);
        unset($data['account_type']);

        return $this->executeInTransaction(function () use ($user, $data) {
            // Handle subdomain (domain -> username)
            if (array_key_exists('domain', $data) && $data['domain'] !== null && $data['domain'] !== '') {
                $slug = Str::slug((string) $data['domain']);
                if ($slug === '') {
                    $this->fail('Invalid subdomain', 'INVALID_SUBDOMAIN', 422);
                }
                $exists = DB::table('users')
                    ->where('username', $slug)
                    ->where('id', '!=', $user->id)
                    ->exists();
                if ($exists) {
                    $this->fail('Subdomain already in use', 'SUBDOMAIN_TAKEN', 422);
                }
                $data['username'] = $slug;
                unset($data['domain']);
            }
            // Normalize city/district (accept id or name)
            if (array_key_exists('city', $data)) {
                $cityId = $this->resolveCityId($data['city']);
                if ($cityId !== null) {
                    $data['city'] = (string) $cityId;
                } else {
                    unset($data['city']);
                }
            } else {
                $cityId = null;
            }

            if (array_key_exists('district', $data)) {
                $districtId = $this->resolveDistrictId($data['district'], $cityId);
                if ($districtId !== null) {
                    $data['state'] = (string) $districtId;
                }
                unset($data['district']);
            }
            // Sync company_name to api_general_settings.site_name (keep defaults for assets)
            if (array_key_exists('company_name', $data) && $data['company_name'] !== null) {
                GeneralSetting::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'site_name' => $data['company_name'],
                        'logo'     => DB::raw("COALESCE(logo, 'logo.png')"),
                        'favicon'  => DB::raw("COALESCE(favicon, 'favicon.png')"),
                    ]
                );
            }

            $user->update($data);

            // Inline plan change when requested
            if (array_key_exists('package_id', $data) && $data['package_id']) {
                $changeType = $data['plan_change_type'] ?? 'immediate';
                $this->changePlan($user->id, (int) $data['package_id'], $changeType, 'Inline plan change via updateUser');
            }

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Resolve a city input (id or name) to city_id (int) from user_districts.
     */
    protected function resolveCityId($input): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }
        if (is_numeric($input)) {
            // verify existence
            $exists = DB::table('user_districts')->where('city_id', (int) $input)->exists();
            return $exists ? (int) $input : null;
        }
        $row = DB::table('user_districts')
            ->select('city_id')
            ->where('city_name_ar', $input)
            ->orWhere('city_name_en', $input)
            ->orderByDesc('id')
            ->first();
        return $row ? (int) $row->city_id : null;
    }

    /**
     * Resolve a district input (id or name) to id (int) from user_districts.
     * Optionally restrict by city_id if provided.
     */
    protected function resolveDistrictId($input, ?int $cityId = null): ?int
    {
        if ($input === null || $input === '') {
            return null;
        }
        if (is_numeric($input)) {
            $q = DB::table('user_districts')->where('id', (int) $input);
            if ($cityId !== null) {
                $q->where('city_id', $cityId);
            }
            return $q->exists() ? (int) $input : null;
        }
        $q = DB::table('user_districts')
            ->select('id')
            ->where(function ($qq) use ($input) {
                $qq->where('name_ar', $input)->orWhere('name_en', $input);
            });
        if ($cityId !== null) {
            $q->where('city_id', $cityId);
        }
        $row = $q->orderByDesc('id')->first();
        return $row ? (int) $row->id : null;
    }

    /**
     * Delete user
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->getUserById($id);

        return $this->executeInTransaction(function () use ($user) {
            // Revoke all tokens before soft-deleting
            try {
                $user->tokens()->delete();
            } catch (\Throwable $e) {
                // ignore token errors; proceed with delete
            }
            return (bool) $user->delete();
        });
    }

    /**
     * Change user password
     *
     * @param int $id
     * @param string $newPassword
     * @return User
     * @throws ResourceNotFoundException
     */
    public function changePassword(int $id, string $newPassword): User
    {
        $user = $this->getUserById($id);

        return $this->executeInTransaction(function () use ($user, $newPassword) {
            $user->password = Hash::make($newPassword);
            $user->save();

            // Revoke all existing tokens (logout from all devices)
            $user->tokens()->delete();

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Update user password (API alias).
     *
     * @param int $id
     * @param string $newPassword
     * @return User
     * @throws ResourceNotFoundException
     */
    public function updatePassword(int $id, string $newPassword): User
    {
        return $this->changePassword($id, $newPassword);
    }

    /**
     * Send password reset code to user (admin-initiated)
     *
     * @param int $userId
     * @param string $method (email or whatsapp)
     * @param string|null $countryCode Optional country code for WhatsApp
     * @return array
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function sendPasswordResetCode(int $userId, string $method, ?string $countryCode = null): array
    {
        $user = $this->getUserById($userId);

        // Validate method
        if (!in_array($method, ['email', 'whatsapp'])) {
            $this->fail('Invalid method. Must be email or whatsapp', 'INVALID_METHOD', 400);
        }

        // Validate user has required contact info
        if ($method === 'email' && empty($user->email)) {
            $this->fail('User does not have an email address', 'USER_NO_EMAIL', 400);
        }

        if ($method === 'whatsapp' && empty($user->phone)) {
            $this->fail('User does not have a phone number', 'USER_NO_PHONE', 400);
        }

        return $this->executeInTransaction(function () use ($user, $method, $countryCode) {
            // Generate 6-digit code
            $code = rand(100000, 999999);
            
            // Create reset log entry
            $resetLog = PasswordResetLog::create([
                'user_id' => $user->id,
                'method' => $method,
                'code' => (string) $code,
                'used' => false,
                'expires_at' => now()->addMinutes(15),
                'attempts' => 1,
                'blocked' => false,
                'blocked_until' => null,
            ]);

            // Get user's preferred language
            $userLanguage = $this->getUserLanguage($user);
            
            // Get frontend URL for reset link
            $frontendUrl = config('app.frontend_url');
            $resetUrl = $frontendUrl . '/reset';

            // Send code based on method
            if ($method === 'email') {
                $emailService = new EmailService();
                $emailSent = $emailService->sendPasswordResetCode(
                    $user->email,
                    $user->name ?? $user->username ?? 'User',
                    (string) $code,
                    $userLanguage,
                    null, // templateName - let service choose
                    $resetUrl,
                    $user->id
                );
                
                if (!$emailSent) {
                    $this->fail('Failed to send reset code via email', 'EMAIL_SEND_FAILED', 500);
                }
            } else {
                // WhatsApp method
                $whatsappService = $this->resolveWhatsAppService();
                
                // Format phone number with country code if provided
                $phoneForSending = $user->phone;
                if ($countryCode) {
                    // Remove + if present and add country code
                    $phoneForSending = ltrim($countryCode, '+') . ltrim($phoneForSending, '+');
                }
                
                try {
                    $whatsappResult = $whatsappService->sendPasswordResetCode(
                        $phoneForSending,
                        (string) $code,
                        $user->name ?? $user->username ?? 'User',
                        $userLanguage,
                        $resetUrl,
                        'password_reset', // templateName
                        $user->id
                    );
                    
                    // WhatsApp service may return string (default message) or bool
                    if ($whatsappResult === false) {
                        $this->fail('Failed to send reset code via WhatsApp', 'WHATSAPP_SEND_FAILED', 500);
                    }
                } catch (\Throwable $e) {
                    $this->fail(
                        'Failed to send reset code via WhatsApp: ' . $e->getMessage(),
                        'WHATSAPP_SEND_FAILED',
                        500
                    );
                }
            }

            // Log activity
            $contact = $method === 'email' ? $user->email : $user->phone;
            $this->logUserActivity(
                $user->id,
                'password_reset_sent',
                "Password reset code sent via {$method} to {$contact}",
                [
                    'method' => $method,
                    'code' => (string) $code,
                    'expires_at' => $resetLog->expires_at->toDateTimeString(),
                ]
            );

            return [
                'code' => (string) $code,
                'expires_at' => $resetLog->expires_at->toDateTimeString(),
            ];
        });
    }

    /**
     * Get user's preferred language or default to Arabic
     *
     * @param User $user
     * @return string
     */
    private function getUserLanguage($user): string
    {
        try {
            $defaultLanguage = $user->languages()->where('is_default', true)->first();
            return $defaultLanguage ? $defaultLanguage->code : 'ar';
        } catch (\Exception $e) {
            // Fallback to Arabic if language lookup fails
            return 'ar';
        }
    }

    /**
     * Ban/Unban user (toggle active status)
     *
     * @param int $id
     * @param bool|null $status
     * @return User
     * @throws ResourceNotFoundException
     */
    public function toggleBan(int $id, ?bool $status = null): User
    {
        $user = $this->getUserById($id);

        return $this->executeInTransaction(function () use ($user, $status) {
            $previousStatus = $user->status;
            
            if ($status === null) {
                $user->status = $user->status === 1 ? 0 : 1;
            } else {
                $user->status = $status ? 1 : 0;
            }

            $user->save();

            // Revoke all tokens when banning (status = 0) so the user can't access while banned
            if ($user->status == 0 && $previousStatus == 1) {
                $user->tokens()->delete();
            }

            // Log activity
            $action = $user->status == 0 ? 'user_banned' : 'user_unbanned';
            $description = $user->status == 0 ? 'User banned' : 'User unbanned';
            
            $this->logUserActivity(
                $user->id,
                $action,
                $description,
                [
                    'previous_status' => $previousStatus,
                    'new_status' => $user->status,
                ]
            );

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Toggle featured status
     *
     * @param int $id
     * @param bool|null $featured
     * @return User
     * @throws ResourceNotFoundException
     */
    public function toggleFeatured(int $id, ?bool $featured = null): User
    {
        $user = $this->getUserById($id);

        return $this->executeInTransaction(function () use ($user, $featured) {
            if ($featured === null) {
                $user->featured = $user->featured ? 0 : 1;
            } else {
                $user->featured = $featured ? 1 : 0;
            }

            $user->save();

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Log activity performed on a user account
     */
    public function logUserActivity(
        int $userId,
        string $action,
        ?string $description = null,
        array $metadata = [],
        ?int $adminId = null
    ): UserActivityLog {
        $user = $this->getUserById($userId);

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
    public function getActivityLog(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->getUserById($userId);

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
    public function getUserInvoices(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $user = $this->getUserById($userId);

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
        int $userId,
        string $message,
        ?string $templateName = null,
        array $templateVariables = []
    ): array {
        $user = $this->getUserById($userId);

        if (empty($user->phone)) {
            $this->fail('User does not have a phone number on file', 'USER_NO_PHONE', 400);
        }

        $whatsappService = $this->resolveWhatsAppService();

        try {
            if ($templateName) {
                $result = $whatsappService->sendTemplateToPhone(
                    $user->phone,
                    $templateName,
                    'ar',
                    $templateVariables
                );

                if (!$result['success']) {
                    throw new \RuntimeException($result['message'] ?? 'Failed to send WhatsApp template message');
                }
            } else {
                $sent = $whatsappService->sendMessage($user->phone, $message);
                if (!$sent) {
                    throw new \RuntimeException('WhatsApp service returned failure response');
                }
            }
        } catch (\Throwable $e) {
            $this->fail(
                'Failed to send WhatsApp message: ' . $e->getMessage(),
                'WHATSAPP_SEND_FAILED',
                500
            );
        }

        $this->logUserActivity(
            $user->id,
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
     * Resolve the WhatsApp service, allowing runtime overrides (e.g. during testing).
     */
    protected function resolveWhatsAppService(): WhatsAppService
    {
        return app(WhatsAppService::class);
    }

    /**
     * Pause/suspend user account
     */
    public function pauseUser(int $userId, string $reason, ?string $adminNotes = null): User
    {
        $user = $this->getUserById($userId);

        return $this->executeInTransaction(function () use ($user, $reason, $adminNotes) {
            if (!$user->active) {
                $this->fail('User is already paused', 'USER_ALREADY_PAUSED', 400);
            }

            $user->active = false;
            $user->save();

            // Revoke all tokens so the user can't access while paused
            $user->tokens()->delete();

            $this->logUserActivity(
                $user->id,
                'user_paused',
                "User paused: {$reason}",
                [
                    'reason' => $reason,
                    'admin_notes' => $adminNotes,
                ]
            );

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Resume paused user account
     */
    public function resumeUser(int $userId): User
    {
        $user = $this->getUserById($userId);

        return $this->executeInTransaction(function () use ($user) {
            if ($user->active) {
                $this->fail('User is already active', 'USER_ALREADY_ACTIVE', 400);
            }

            $user->active = true;
            $user->save();

            $this->logUserActivity(
                $user->id,
                'user_resumed',
                'User account resumed',
                []
            );

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Change user's subscription plan
     */
    public function changePlan(
        int $userId,
        int $newPlanId,
        string $changeType,
        ?string $adminNotes = null
    ): User {
        $user = $this->getUserById($userId);

        $newPlan = Plan::active()->find($newPlanId);
        if (!$newPlan) {
            $this->fail('Selected plan is not available', 'PLAN_NOT_AVAILABLE', 404);
        }

        $currentSubscription = $user->activeMembership;
        if (!$currentSubscription) {
            $this->fail('User does not have an active subscription', 'NO_ACTIVE_SUBSCRIPTION', 400);
        }

        if ($currentSubscription->package_id === $newPlan->id) {
            $this->fail('User is already on this plan', 'PLAN_ALREADY_APPLIED', 400);
        }

        return $this->executeInTransaction(function () use ($user, $newPlan, $currentSubscription, $changeType, $adminNotes) {
            $previousPlan = $currentSubscription->package;
            $tenant = TenantUser::findOrFail($user->id);
            $package = Package::findOrFail($newPlan->id);
            $membershipService = app(MembershipService::class);

            if ($changeType === 'immediate') {
                $membershipService->activateImmediateMembership($tenant, $package, [
                    'payment_method' => 'admin_change',
                    'transaction_id' => 'ADMIN_PLAN_CHANGE_' . now()->timestamp,
                    'price' => (float) $newPlan->price,
                    'source' => 'admin_change',
                ]);
            } else {
                $membershipService->queueNextMembership($tenant, $package, [
                    'payment_method' => 'admin_change_scheduled',
                    'transaction_id' => 'ADMIN_PLAN_SCHEDULED_' . now()->timestamp,
                ]);
            }

            $this->logUserActivity(
                $user->id,
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

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Cancel user's active subscription
     */
    public function cancelSubscription(
        int $userId,
        string $cancelType,
        string $reason,
        ?string $adminNotes = null
    ): User {
        $user = $this->getUserById($userId);

        $subscription = $user->activeMembership;
        if (!$subscription) {
            $this->fail('User has no active subscription', 'NO_ACTIVE_SUBSCRIPTION', 400);
        }

        return $this->executeInTransaction(function () use ($user, $subscription, $cancelType, $reason, $adminNotes) {
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
                $user->id,
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

            return $user->fresh(['referrer', 'activeMembership.package']);
        });
    }

    /**
     * Normalize list filters for repository consumption.
     *
     * @param array $filters
     * @return array
     */
    protected function normalizeListFilters(array $filters): array
    {
        $normalized = [];

        if (isset($filters['search']) && trim($filters['search']) !== '') {
            $normalized['search'] = trim($filters['search']);
        }

        foreach (['start_date', 'end_date', 'referred_by'] as $key) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $normalized[$key] = $filters[$key];
            }
        }

        if (array_key_exists('status', $filters)) {
            $statusValue = $filters['status'];

            if (is_numeric($statusValue)) {
                $normalized['status'] = (int) $statusValue;
            } else {
                $statusKey = strtolower((string) $statusValue);
                $statusMap = [
                    'active' => 1,
                    'approved' => 1,
                    'banned' => 0,
                    'inactive' => 0,
                    'disabled' => 0,
                    'pending' => 2,
                ];

                if (array_key_exists($statusKey, $statusMap)) {
                    $normalized['status'] = $statusMap[$statusKey];
                } elseif ($statusKey === 'paused') {
                    $normalized['active'] = false;
                }
            }
        }

        if (array_key_exists('featured', $filters)) {
            $featured = filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($featured !== null) {
                $normalized['featured'] = $featured ? 1 : 0;
            }
        }

        if (!empty($filters['plan'])) {
            $normalized['plan'] = $filters['plan'];
        }

        if (array_key_exists('subscription_status', $filters)) {
            $status = strtolower((string) $filters['subscription_status']);
            if ($status === 'active') {
                $normalized['has_active_subscription'] = true;
            } elseif (in_array($status, ['none', 'inactive'], true)) {
                $normalized['has_active_subscription'] = false;
            } elseif (in_array($status, ['trial', 'expired'], true)) {
                $normalized['subscription_status'] = $status;
            }
        }

        [$orderBy, $orderDir] = $this->resolveSort($filters['sort'] ?? null);
        $normalized['order_by'] = $orderBy;
        $normalized['order_dir'] = $orderDir;

        return $normalized;
    }

    /**
     * Resolve sort parameter into column and direction.
     *
     * @param string|null $sort
     * @return array{0:string,1:string}
     */
    protected function resolveSort(?string $sort): array
    {
        $default = ['created_at', 'desc'];

        if (empty($sort)) {
            return $default;
        }

        $sort = strtolower(trim($sort));

        $aliases = [
            'latest' => ['created_at', 'desc'],
            'oldest' => ['created_at', 'asc'],
            'name_asc' => ['username', 'asc'],
            'name_desc' => ['username', 'desc'],
            'company_asc' => ['company_name', 'asc'],
            'company_desc' => ['company_name', 'desc'],
        ];

        if (isset($aliases[$sort])) {
            return $aliases[$sort];
        }

        if (str_contains($sort, ':')) {
            [$field, $direction] = explode(':', $sort, 2);
        } elseif (str_contains($sort, '|')) {
            [$field, $direction] = explode('|', $sort, 2);
        } elseif (str_contains($sort, ',')) {
            [$field, $direction] = explode(',', $sort, 2);
        } else {
            $field = $sort;
            $direction = 'desc';
        }

        $allowedFields = ['created_at', 'username', 'company_name', 'email', 'status'];
        $field = in_array($field, $allowedFields, true) ? $field : 'created_at';
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        return [$field, $direction];
    }

    /**
     * Resolve plan term into duration in days.
     */
    protected function resolvePlanDurationInDays(?string $term): int
    {
        if (empty($term)) {
            return 30;
        }

        $normalized = strtolower(trim($term));

        return match (true) {
            $normalized === 'monthly' => 30,
            $normalized === 'quarterly' => 90,
            $normalized === 'semiannual',
            $normalized === 'semi-annual' => 182,
            $normalized === 'yearly',
            $normalized === 'annual' => 365,
            is_numeric($normalized) => max((int) $normalized, 1),
            default => 30,
        };
    }
}
