<?php

namespace App\Services;

use App\Models\User;
use App\Models\Api\GeneralSetting;
use App\Models\Membership;
use App\Models\BasicExtended;
use App\Models\Package;
use App\Http\Helpers\UserPermissionHelper;
use App\Exceptions\BusinessLogicException;
use App\Services\UserPackageService;
use App\Services\WhatsAppService;
use App\Events\UserDowngradedToFree;
use App\Events\UserUpgradedFromFree;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MembershipService
{
    const FREE_PACKAGE_ID = 16;
    const PAID_YEARLY_PACKAGE_ID = 24;
    const PAID_MONTHLY_PACKAGE_ID = 26;
    const TRIAL_PACKAGE_ID = 26;

    // Package terms
    const TERM_MONTHLY = 'monthly';
    const TERM_YEARLY = 'yearly';
    const TERM_LIFETIME = 'lifetime';
    const TERM_TRIAL = 'trial';

    protected $userPackageService;
    protected $whatsappService;

    public function __construct(UserPackageService $userPackageService, WhatsAppService $whatsappService)
    {
        $this->userPackageService = $userPackageService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Handle user membership expiration
     */
    public function handleMembershipExpiration(User $user): void
    {
        $currentPackage = UserPermissionHelper::userPackage($user->id);

        if ($this->shouldDowngradeToFree($currentPackage)) {
            $this->downgradeToFreePackage($user);
        }
    }

    /**
     * Check if user should be downgraded to free package
     */
    private function shouldDowngradeToFree($currentPackage): bool
    {
        return is_null($currentPackage);
    }

    /**
     * Downgrade user to free package with all related actions
     */
    private function downgradeToFreePackage(User $user): void
    {
        Log::info("Downgrading user {$user->id} to free package");

        // Get previous package info for event
        $previousPackage = \App\Models\Membership::where('user_id', $user->id)
            ->where('status', 1)
            ->whereDate('expire_date', '<', now()->toDateString())
            ->with('package')
            ->first();

        // 1. Assign free package
        $this->assignFreePackage($user);

        // 2. Enable maintenance mode
        $this->enableMaintenanceMode($user);

        // 3. Send notifications
        $this->sendExpirationNotifications($user);

        // 4. Set user message
        $this->setUserMessage($user);

        // 5. Ensure user has language data
        $this->ensureUserLanguageData($user);

        // 6. Fire event for other listeners
        event(new UserDowngradedToFree($user, $previousPackage ? $previousPackage->package : null));
    }

    /**
     * Assign free package to user
     */
    private function assignFreePackage(User $user): void
    {
        $freePackage = Package::find(self::FREE_PACKAGE_ID);

        if (!$freePackage || $freePackage->status != '1') {
            Log::error("Free package (ID: " . self::FREE_PACKAGE_ID . ") not found or inactive for user {$user->id}");
            return;
        }

        $request = new Request([
            'user_id' => $user->id,
            'package_id' => self::FREE_PACKAGE_ID,
            'payment_method' => 'system',
        ]);

        $this->userPackageService->addCurrentPackage($request);
        Log::info("Assigned free package to user {$user->id}");
    }

    /**
     * Enable maintenance mode for user
     */
    public function enableMaintenanceMode(User $user): void
    {
        $setting = $this->getOrCreateGeneralSetting($user);
        $setting->maintenance_mode = 1;
        $setting->save();

        Log::info("Enabled maintenance mode for user {$user->id}");
    }

    /**
     * Disable maintenance mode for user
     */
    public function disableMaintenanceMode(User $user): void
    {
        $setting = $this->getOrCreateGeneralSetting($user);
        $setting->maintenance_mode = 0;
        $setting->save();

        Log::info("Disabled maintenance mode for user {$user->id}");
    }

    /**
     * Check if user can control maintenance mode
     */
    public function canControlMaintenanceMode(User $user): bool
    {
        $currentMembership = $this->getCurrentMembership($user);
        if (!$currentMembership) {
            return false;
        }

        // Only free package users cannot control maintenance mode
        return $currentMembership->package_id !== self::FREE_PACKAGE_ID;
    }

    /**
     * Check if user has free package
     */
    public function hasFreePackage(User $user): bool
    {
        $currentMembership = $this->getCurrentMembership($user);
        return $currentMembership && $currentMembership->package_id === self::FREE_PACKAGE_ID;
    }

    /**
     * Check if user has trial package
     */
    public function hasTrialPackage(User $user): bool
    {
        $currentMembership = $this->getCurrentMembership($user);
        return $currentMembership && $currentMembership->package_id === self::TRIAL_PACKAGE_ID;
    }

    /**
     * Check if user has paid package (monthly or yearly)
     */
    public function hasPaidPackage(User $user): bool
    {
        $currentMembership = $this->getCurrentMembership($user);
        if (!$currentMembership) {
            return false;
        }

        $package = Package::find($currentMembership->package_id);
        if (!$package) {
            return false;
        }

        // Paid packages are monthly or yearly (not free, trial, or lifetime)
        return in_array($package->term, [self::TERM_MONTHLY, self::TERM_YEARLY]);
    }

    /**
     * Get user's current package term
     */
    public function getCurrentPackageTerm(User $user): ?string
    {
        $currentMembership = $this->getCurrentMembership($user);
        if (!$currentMembership) {
            return null;
        }

        $package = Package::find($currentMembership->package_id);
        return $package ? $package->term : null;
    }

    /**
     * Check if user's package is expiring soon (within specified days)
     */
    public function isPackageExpiringSoon(User $user, int $days = 7): bool
    {
        $currentMembership = $this->getCurrentMembership($user);
        if (!$currentMembership) {
            return false;
        }

        $expiryDate = Carbon::parse($currentMembership->expire_date);
        $warningDate = now()->addDays($days);

        return $expiryDate->lte($warningDate);
    }

    /**
     * Get days until package expires
     */
    public function getDaysUntilExpiry(User $user): ?int
    {
        $currentMembership = $this->getCurrentMembership($user);
        if (!$currentMembership) {
            return null;
        }

        $expiryDate = Carbon::parse($currentMembership->expire_date);
        return now()->diffInDays($expiryDate, false);
    }

    /**
     * Get current active membership for user
     */
    private function getCurrentMembership(User $user): ?\App\Models\Membership
    {
        return \App\Models\Membership::where('user_id', $user->id)
            ->where('status', 1)
            ->where('start_date', '<=', now()->format('Y-m-d'))
            ->where('expire_date', '>=', now()->format('Y-m-d'))
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get or create general setting for user
     */
    private function getOrCreateGeneralSetting(User $user): GeneralSetting
    {
        return GeneralSetting::firstOrCreate(
            ['user_id' => $user->id],
            ['maintenance_mode' => 0]
        );
    }

    /**
     * Send expiration notifications
     */
    private function sendExpirationNotifications(User $user): void
    {
        try {
            // Get user's expired membership info for WhatsApp message
            $expiredMembership = \App\Models\Membership::where('user_id', $user->id)
                ->where('status', 1)
                ->whereDate('expire_date', '<', now()->toDateString())
                ->with('package')
                ->first();

            $packageName = $expiredMembership && $expiredMembership->package
                ? $expiredMembership->package->title
                : 'الباقة السابقة';
            $expiryDate = $expiredMembership
                ? Carbon::parse($expiredMembership->expire_date)->format('Y-m-d')
                : now()->format('Y-m-d');

            // Send WhatsApp notification
            $bs = \App\Models\BasicSetting::first();
            if (!empty($user->phone) && $bs && $bs->subscription_expired_enabled) {
                $subscriptionExpiredMessage = '{name}، انتهت صلاحية اشتراكك في {package_name} في {expiry_date}. يرجى تجديد اشتراكك لاستعادة الخدمة.';

                $this->whatsappService->sendSubscriptionExpiredMessage(
                    $user->phone,
                    $subscriptionExpiredMessage,
                    $user->first_name ?? $user->username,
                    $packageName,
                    $expiryDate
                );

                Log::info("Sent WhatsApp subscription expired notification to user {$user->id}");
            }

            // Send email notification
            $be = \App\Models\BasicExtended::first();
            if (!empty($user->email) && $be && $be->subscription_expired_email_enabled) {
                \App\Jobs\FreePackageSwitchMail::dispatch($user, $bs, $be);
                Log::info("Sent email notification to user {$user->id}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to send notifications to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Send renewal success notifications
     */
    private function sendRenewalNotifications(User $user, Package $newPackage, string $source = 'payment'): void
    {
        try {
            $bs = \App\Models\BasicSetting::first();
            $be = \App\Models\BasicExtended::first();

            $message = 'Your membership has been renewed! Your website is now active and accessible to visitors.';

            // Send WhatsApp notification if enabled
            if (!empty($user->phone) && $bs) {
                $this->whatsappService->sendMessage(
                    $user->phone,
                    $message
                );

                Log::info("Sent WhatsApp renewal notification to user {$user->id} via {$source}");
            }

            // Send email notification if enabled
            if (!empty($user->email) && $be) {
                // You can create a dedicated mail job for renewal notifications
                // For now, we'll log it
                Log::info("Email renewal notification queued for user {$user->id} via {$source}");
            }

        } catch (\Exception $e) {
            Log::error("Failed to send renewal notifications to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Set user message about free package
     */
    private function setUserMessage(User $user): void
    {
        $user->message = 'تم تحويلك إلى الباقة المجانية بعد انتهاء فترة التجربة. يمكنك ترقية باقاتك في أي وقت من لوحة التحكم.';
        $user->save();
    }

    /**
     * Ensure user has language data
     */
    private function ensureUserLanguageData(User $user): void
    {
        if ($user->languages()->count() == 0) {
            $deLang = \App\Models\User\Language::where('user_id', 0)->first();
            if ($deLang) {
                $lang = new \App\Models\User\Language;
                $lang->name = $deLang->name;
                $lang->code = $deLang->code;
                $lang->is_default = 1;
                $lang->rtl = $deLang->rtl;
                $lang->user_id = $user->id;
                $lang->keywords = $deLang->keywords;
                $lang->save();

                Log::info("Created missing language for user {$user->id}");
            }
        }
    }

    /**
     * Calculate membership expiry from package term and billing period.
     */
    public function calculateExpireDate(Package $package, Carbon $startDate, int $period = 1): Carbon
    {
        $period = max(1, $period);

        return match ($package->term) {
            self::TERM_MONTHLY => $startDate->copy()->addMonths($period),
            self::TERM_YEARLY => $startDate->copy()->addYears($period),
            self::TERM_LIFETIME => Carbon::maxValue(),
            'daily' => $startDate->copy()->addDays($period),
            'weekly' => $startDate->copy()->addWeeks($period),
            default => $startDate->copy()->addMonths($period),
        };
    }

    /**
     * Expected charge for a membership checkout (price × period; lifetime ignores period).
     */
    public function calculateExpectedMembershipAmount(Package $package, int $period = 1): float
    {
        if ($package->term === self::TERM_LIFETIME) {
            return (float) $package->price;
        }

        return (float) $package->price * max(1, $period);
    }

    /**
     * Expire current and pending memberships so only one plan can be active.
     */
    public function expireActiveMemberships(int $userId, ?int $exceptId = null): void
    {
        $yesterday = Carbon::now()->subDay()->format('Y-m-d');
        $today = Carbon::now()->format('Y-m-d');

        $query = Membership::query()->where('user_id', $userId);

        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }

        $memberships = $query
            ->whereYear('start_date', '<>', 9999)
            ->where(function ($q) use ($today) {
                $q->where(function ($active) use ($today) {
                    $active->where('status', 1)
                        ->whereDate('start_date', '<=', $today)
                        ->whereDate('expire_date', '>=', $today);
                })->orWhere('status', 0);
            })
            ->get();

        foreach ($memberships as $membership) {
            $membership->expire_date = $yesterday;
            $membership->modified = 1;
            if ((int) $membership->status === 0) {
                $membership->status = 2;
            }
            $membership->save();
        }
    }

    /**
     * Activate a package immediately: expire previous memberships and create a new active row.
     */
    public function activateImmediateMembership(User $user, Package $package, array $options = []): Membership
    {
        $period = max(1, (int) ($options['period'] ?? 1));
        $paymentMethod = (string) ($options['payment_method'] ?? 'system');
        $transactionId = (string) ($options['transaction_id'] ?? uniqid());
        $price = array_key_exists('price', $options)
            ? (float) $options['price']
            : $this->calculateExpectedMembershipAmount($package, $period);
        $source = (string) ($options['source'] ?? 'payment');
        $exceptId = $options['except_membership_id'] ?? null;

        if ($options['expire_previous'] ?? true) {
            $this->expireActiveMemberships($user->id, $exceptId);
        }

        $be = BasicExtended::first();
        $startDate = Carbon::now();
        $expireDate = $this->calculateExpireDate($package, $startDate, $period);

        $membership = Membership::create([
            'package_price' => $package->price,
            'discount' => $options['discount'] ?? 0,
            'coupon_code' => $options['coupon_code'] ?? null,
            'price' => $price,
            'currency' => $be->base_currency_text ?? 'SAR',
            'currency_symbol' => $be->base_currency_symbol ?? 'SAR',
            'payment_method' => $paymentMethod,
            'transaction_id' => $transactionId,
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'receipt' => $options['receipt'] ?? null,
            'transaction_details' => $options['transaction_details'] ?? null,
            'settings' => json_encode($be),
            'package_id' => $package->id,
            'user_id' => $user->id,
            'start_date' => $startDate->format('Y-m-d'),
            'expire_date' => $expireDate->format('Y-m-d'),
            'conversation_id' => $options['conversation_id'] ?? null,
        ]);

        $user->subscribed = true;
        $user->subscription_amount = $package->price;
        $user->save();

        if (!($options['skip_upgrade_hooks'] ?? false)) {
            $this->handlePackageUpgrade($user, $package->id, $source);
            $this->handlePackageDowngrade($user, $package->id);
        }

        return $membership;
    }

    /**
     * Queue a package to start after the current membership expires (next cycle).
     *
     * @throws BusinessLogicException
     */
    public function queueNextMembership(User $user, Package $package, array $options = []): Membership
    {
        if (UserPermissionHelper::hasPendingMembership($user->id)) {
            throw new BusinessLogicException(
                'User already has a pending package. Resolve it before scheduling a next cycle change.',
                'PENDING_MEMBERSHIP_EXISTS',
                400
            );
        }

        $currentMembership = UserPermissionHelper::userPackage($user->id);
        if (!$currentMembership) {
            throw new BusinessLogicException(
                'User does not have an active subscription to schedule a next cycle change.',
                'NO_ACTIVE_SUBSCRIPTION',
                400
            );
        }

        if ((int) $currentMembership->is_trial === 1) {
            throw new BusinessLogicException(
                'Cannot schedule next cycle while user is on a trial package.',
                'TRIAL_PACKAGE_ACTIVE',
                400
            );
        }

        $currentPackage = Package::find($currentMembership->package_id);
        if (!$currentPackage) {
            throw new BusinessLogicException('Current package not found.', 'PACKAGE_NOT_FOUND', 404);
        }

        if ($currentPackage->term === self::TERM_LIFETIME) {
            throw new BusinessLogicException(
                'Cannot schedule next cycle while user is on a lifetime package.',
                'LIFETIME_PACKAGE_ACTIVE',
                400
            );
        }

        if (UserPermissionHelper::nextMembership($user->id)) {
            throw new BusinessLogicException(
                'User already has a queued next package.',
                'NEXT_PACKAGE_EXISTS',
                400
            );
        }

        $be = BasicExtended::first();
        $startDate = Carbon::parse($currentMembership->expire_date)->addDay();
        $expireDate = $this->calculateExpireDate($package, $startDate, 1);

        return Membership::create([
            'package_price' => $package->price,
            'discount' => 0,
            'price' => $package->price,
            'currency' => $be->base_currency_text ?? 'SAR',
            'currency_symbol' => $be->base_currency_symbol ?? 'SAR',
            'payment_method' => (string) ($options['payment_method'] ?? 'admin_change_scheduled'),
            'transaction_id' => (string) ($options['transaction_id'] ?? uniqid()),
            'status' => 1,
            'is_trial' => 0,
            'trial_days' => 0,
            'settings' => json_encode($be),
            'package_id' => $package->id,
            'user_id' => $user->id,
            'start_date' => $startDate->format('Y-m-d'),
            'expire_date' => $expireDate->format('Y-m-d'),
        ]);
    }

    /**
     * Apply upgrade/downgrade side effects after a membership is activated or approved.
     *
     * @param User|\App\Domain\User\Models\User|int $user
     */
    public function applyPackageTransitionHooks($user, int $packageId, string $source = 'payment'): void
    {
        $tenant = $this->resolveTenantUser($user);
        $this->handlePackageUpgrade($tenant, $packageId, $source);
        $this->handlePackageDowngrade($tenant, $packageId);
    }

    /**
     * Normalize tenant user models to App\Models\User.
     *
     * @param User|\App\Domain\User\Models\User|int $user
     */
    private function resolveTenantUser($user): User
    {
        if ($user instanceof User) {
            return $user;
        }

        $userId = is_int($user) ? $user : $user->id;

        return User::findOrFail($userId);
    }

    /**
     * Handle package upgrade - disable maintenance mode if user upgrades from free/trial
     */
    public function handlePackageUpgrade(User $user, int $newPackageId, string $source = 'payment'): void
    {
        $newPackage = Package::find($newPackageId);
        if (!$newPackage) {
            Log::error("Package not found for upgrade: {$newPackageId}");
            return;
        }

        // Check if upgrading TO a non-free/non-trial package
        $isUpgradingToNonFree = !in_array($newPackageId, [self::FREE_PACKAGE_ID, self::TRIAL_PACKAGE_ID]);

        // Check previous membership to see what package they WERE on
        // Look for the most recent membership BEFORE this upgrade
        $previousMembership = \App\Models\Membership::where('user_id', $user->id)
            ->where('package_id', '!=', $newPackageId) // Not the new package we're upgrading to
            ->orderBy('created_at', 'desc')
            ->first();

        $wasOnFreePackage = $previousMembership && $previousMembership->package_id === self::FREE_PACKAGE_ID;
        $wasOnTrialPackage = $previousMembership && $previousMembership->package_id === self::TRIAL_PACKAGE_ID;

        // Also check if maintenance mode is currently enabled (regardless of previous package)
        $maintenanceIsEnabled = $this->isMaintenanceModeEnabled($user);

        // Disable maintenance mode if:
        // 1. Upgrading from free/trial to paid package, OR
        // 2. Maintenance mode is ON and upgrading to any non-free/non-trial package
        $shouldDisableMaintenance = ($wasOnFreePackage || $wasOnTrialPackage || $maintenanceIsEnabled) && $isUpgradingToNonFree;

        if ($shouldDisableMaintenance) {
            $this->disableMaintenanceMode($user);

            // Send renewal success notifications
            $this->sendRenewalNotifications($user, $newPackage, $source);

            // Fire event for upgrade
            event(new UserUpgradedFromFree($user, $newPackage));

            $fromPackage = $previousMembership && $previousMembership->package ? $previousMembership->package->title : 'Unknown';
            Log::info("Disabled maintenance mode for user {$user->id} after package upgrade from {$fromPackage} to {$newPackage->title} (ID: {$newPackageId}, Term: {$newPackage->term}, Source: {$source})");
        } else {
            Log::info("Package upgrade for user {$user->id} to {$newPackage->title} (ID: {$newPackageId}, Term: {$newPackage->term}, Source: {$source}) - no maintenance mode change needed (wasOnFree: " . ($wasOnFreePackage ? 'yes' : 'no') . ", wasOnTrial: " . ($wasOnTrialPackage ? 'yes' : 'no') . ", maintenanceOn: " . ($maintenanceIsEnabled ? 'yes' : 'no') . ")");
        }
    }

    /**
     * Handle package downgrade - enable maintenance mode if user downgrades to free
     */
    public function handlePackageDowngrade(User $user, int $newPackageId): void
    {
        $newPackage = Package::find($newPackageId);
        if (!$newPackage) {
            Log::error("Package not found for downgrade: {$newPackageId}");
            return;
        }

        // Enable maintenance mode if downgrading to free package
        if ($newPackageId === self::FREE_PACKAGE_ID) {
            $this->enableMaintenanceMode($user);

            // Fire event for downgrade
            event(new UserDowngradedToFree($user, $this->getCurrentMembership($user) ? $this->getCurrentMembership($user)->package : null));

            Log::info("Enabled maintenance mode for user {$user->id} after package downgrade to free package");
        }
    }

    /**
     * Get comprehensive membership status for user
     */
    public function getMembershipStatus(User $user): array
    {
        $currentMembership = $this->getCurrentMembership($user);
        $package = $currentMembership ? Package::find($currentMembership->package_id) : null;

        return [
            'has_membership' => $currentMembership !== null,
            'package_id' => $currentMembership ? $currentMembership->package_id : null,
            'package_name' => $package ? $package->title : null,
            'package_term' => $package ? $package->term : null,
            'is_free' => $this->hasFreePackage($user),
            'is_trial' => $this->hasTrialPackage($user),
            'is_paid' => $this->hasPaidPackage($user),
            'can_control_maintenance' => $this->canControlMaintenanceMode($user),
            'expires_at' => $currentMembership ? $currentMembership->expire_date : null,
            'days_until_expiry' => $this->getDaysUntilExpiry($user),
            'is_expiring_soon' => $this->isPackageExpiringSoon($user),
            'maintenance_mode_enabled' => $this->isMaintenanceModeEnabled($user),
        ];
    }

    /**
     * Check if maintenance mode is currently enabled for user
     */
    public function isMaintenanceModeEnabled(User $user): bool
    {
        $setting = GeneralSetting::where('user_id', $user->id)->first();
        return $setting && $setting->maintenance_mode == 1;
    }

    /**
     * Get free package details
     */
    public function getFreePackage(): ?Package
    {
        return Package::find(self::FREE_PACKAGE_ID);
    }

    /**
     * Check if package is free package
     */
    public function isFreePackage(int $packageId): bool
    {
        return $packageId === self::FREE_PACKAGE_ID;
    }
}
