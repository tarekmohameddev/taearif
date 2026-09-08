<?php

namespace App\Services\Membership;

use App\Models\Api\GeneralSetting;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use Carbon\Carbon;

class MembershipAccessStateService
{
    public const SCHEMA_VERSION = 1;

    public const PLAN_FREE = 'free';
    public const PLAN_TRIAL = 'trial';
    public const PLAN_PAID = 'paid';
    public const PLAN_NONE = 'none';
    public const PLAN_UNKNOWN = 'unknown';

    public const ENTITLEMENT_ACTIVE = 'active';
    public const ENTITLEMENT_EXPIRED = 'expired';
    public const ENTITLEMENT_PENDING = 'pending';
    public const ENTITLEMENT_NONE = 'none';
    public const ENTITLEMENT_INVALID = 'invalid';

    public const WEBSITE_ACTIVE = 'active';
    public const WEBSITE_RESTRICTED = 'restricted';

    public const WEBSITE_REASON_SUBSCRIPTION_REQUIRED = 'subscription_required';
    public const WEBSITE_REASON_MANUAL_MAINTENANCE = 'manual_maintenance';
    public const WEBSITE_REASON_ACCOUNT_INACTIVE = 'account_inactive';

    public const TRANSITION_TRIAL_EXPIRED = 'trial_expired';
    public const TRANSITION_PAID_EXPIRED = 'paid_expired';
    public const TRANSITION_MANUAL_FREE = 'manual_free';

    public function contextForUser(User $user): array
    {
        return $this->buildContext($this->resolveOwner($user), true);
    }

    public function contextForTenant(User $tenant): array
    {
        return $this->buildContext($this->resolveOwner($tenant), true);
    }

    public function forUser(User $user): array
    {
        return $this->contextForUser($user)['state'];
    }

    public function forTenant(User $tenant): array
    {
        return $this->contextForTenant($tenant)['state'];
    }

    public function publicForTenant(User $tenant): array
    {
        $state = $this->buildContext($this->resolveOwner($tenant), false)['state'];

        return [
            'subscription' => [
                'schema_version' => self::SCHEMA_VERSION,
                'plan_type' => $state['subscription']['plan']['type'],
                'premium_access' => $state['subscription']['premium_access'],
                'transition_reason' => data_get($state, 'subscription.transition.reason'),
            ],
            'website_access' => $state['website_access'],
        ];
    }

    public function resolveEffectiveMembershipForUser(User $user): ?Membership
    {
        return $this->contextForUser($user)['membership'];
    }

    public function classifyPackage(?Membership $membership, ?Package $package): string
    {
        if (!$membership || !$package) {
            return self::PLAN_UNKNOWN;
        }

        $freePackageId = (int) config('membership.free_package_id', MembershipService::FREE_PACKAGE_ID);
        if ((int) $package->id === $freePackageId) {
            return self::PLAN_FREE;
        }

        $trialPackageIds = array_filter([
            (int) config('membership.trial_package_id', MembershipService::TRIAL_PACKAGE_ID),
            (int) config('membership.trial_monthly_package_id', MembershipService::TRIAL_MONTHLY_PACKAGE_ID),
        ]);

        if (in_array((int) $package->id, $trialPackageIds, true)
            || (int) $membership->is_trial === 1
            || (int) ($package->is_trial ?? 0) === 1
            || (string) $package->term === MembershipService::TERM_TRIAL) {
            return self::PLAN_TRIAL;
        }

        return self::PLAN_PAID;
    }

    public function profileCacheTtl(array $state, int $defaultTtl = 3600): int
    {
        $expiresAt = data_get($state, 'subscription.expires_at');
        $entitlementStatus = data_get($state, 'subscription.entitlement_status');

        if (!$expiresAt || $entitlementStatus !== self::ENTITLEMENT_ACTIVE) {
            return $defaultTtl;
        }

        $boundarySeconds = now()->diffInSeconds(Carbon::parse($expiresAt)->endOfDay()->addMinutes(5), false);

        if ($boundarySeconds <= 0) {
            return 60;
        }

        return min($defaultTtl, max(60, $boundarySeconds));
    }

    protected function buildContext(User $tenant, bool $includePrivateTransition): array
    {
        $currentMembership = $this->resolveCurrentMembership($tenant);
        $latestMembership = $currentMembership ? null : $this->resolveLatestMembership($tenant);
        $effectiveMembership = $currentMembership ?: $latestMembership;

        $subscription = $this->buildSubscription($currentMembership, $latestMembership, $includePrivateTransition);
        $state = [
            'subscription' => $subscription,
            'website_access' => $this->buildWebsiteAccess($tenant, $subscription, $effectiveMembership),
        ];

        return [
            'state' => $state,
            'membership' => $effectiveMembership,
        ];
    }

    protected function resolveCurrentMembership(User $tenant): ?Membership
    {
        $today = now()->toDateString();

        return Membership::query()
            ->with('package')
            ->where('user_id', $tenant->id)
            ->where('status', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('expire_date', '>=', $today)
            ->orderByDesc('id')
            ->first();
    }

    protected function buildSubscription(?Membership $currentMembership, ?Membership $latestMembership, bool $includePrivateTransition): array
    {
        if ($currentMembership) {
            $package = $currentMembership->package;
            $planType = $this->classifyPackage($currentMembership, $package);

            return [
                'schema_version' => self::SCHEMA_VERSION,
                'plan' => [
                    'id' => $currentMembership->package_id ? (int) $currentMembership->package_id : null,
                    'type' => $planType,
                    'title' => $package ? $package->getDisplayTitle('ar', $currentMembership) : null,
                ],
                'entitlement_status' => self::ENTITLEMENT_ACTIVE,
                'premium_access' => in_array($planType, [self::PLAN_TRIAL, self::PLAN_PAID], true),
                'starts_at' => $this->toDateString($currentMembership->start_date),
                'expires_at' => $this->toDateString($currentMembership->expire_date),
                'days_remaining' => $this->daysRemaining($currentMembership),
                'transition' => $this->buildTransition($currentMembership, $includePrivateTransition),
            ];
        }

        $planType = self::PLAN_NONE;
        $entitlementStatus = self::ENTITLEMENT_NONE;

        if ($latestMembership) {
            $package = $latestMembership->package;
            $planType = $this->classifyPackage($latestMembership, $package);

            if ((int) $latestMembership->status === 1 && $latestMembership->start_date && Carbon::parse($latestMembership->start_date)->isFuture()) {
                $entitlementStatus = self::ENTITLEMENT_PENDING;
            } elseif ((int) $latestMembership->status === 0) {
                $entitlementStatus = self::ENTITLEMENT_PENDING;
            } elseif ((int) $latestMembership->status === 1 && $latestMembership->expire_date && Carbon::parse($latestMembership->expire_date)->lt(Carbon::today())) {
                $entitlementStatus = self::ENTITLEMENT_EXPIRED;
            } else {
                $entitlementStatus = self::ENTITLEMENT_INVALID;
            }
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'plan' => [
                'id' => $latestMembership ? (int) $latestMembership->package_id : null,
                'type' => $planType,
                'title' => $latestMembership && $latestMembership->package ? $latestMembership->package->getDisplayTitle('ar', $latestMembership) : null,
            ],
            'entitlement_status' => $entitlementStatus,
            'premium_access' => false,
            'starts_at' => $latestMembership ? $this->toDateString($latestMembership->start_date) : null,
            'expires_at' => $latestMembership ? $this->toDateString($latestMembership->expire_date) : null,
            'days_remaining' => $latestMembership && $entitlementStatus === self::ENTITLEMENT_PENDING
                ? max(0, Carbon::today()->diffInDays(Carbon::parse($latestMembership->start_date), false))
                : 0,
            'transition' => null,
        ];
    }

    protected function resolveLatestMembership(User $tenant): ?Membership
    {
        return Membership::query()
            ->with('package')
            ->where('user_id', $tenant->id)
            ->orderByDesc('id')
            ->first();
    }

    protected function buildTransition(Membership $membership, bool $includePrivateTransition): ?array
    {
        $reason = $membership->transition_reason ?: null;
        if (!$reason) {
            return null;
        }

        $transition = [
            'reason' => $reason,
        ];

        if (!$includePrivateTransition) {
            return $transition;
        }

        $previousMembership = null;
        if (!empty($membership->previous_membership_id)) {
            $previousMembership = Membership::query()
                ->with('package')
                ->find($membership->previous_membership_id);
        }

        $transition['previous_membership_id'] = $membership->previous_membership_id ? (int) $membership->previous_membership_id : null;
        $transition['previous_plan_type'] = $previousMembership
            ? $this->classifyPackage($previousMembership, $previousMembership->package)
            : null;
        $transition['ended_at'] = $previousMembership ? $this->toDateString($previousMembership->expire_date) : null;

        return $transition;
    }

    protected function buildWebsiteAccess(User $tenant, array $subscription, ?Membership $currentMembership): array
    {
        if (!$tenant->active || (int) $tenant->status === 0) {
            return [
                'status' => self::WEBSITE_RESTRICTED,
                'allowed' => false,
                'reason' => self::WEBSITE_REASON_ACCOUNT_INACTIVE,
            ];
        }

        $maintenanceMode = (bool) optional(GeneralSetting::where('user_id', $tenant->id)->first())->maintenance_mode;
        if (!$maintenanceMode) {
            return [
                'status' => self::WEBSITE_ACTIVE,
                'allowed' => true,
                'reason' => null,
            ];
        }

        $reason = self::WEBSITE_REASON_MANUAL_MAINTENANCE;
        if ($currentMembership && in_array((string) $currentMembership->transition_reason, [
            self::TRANSITION_TRIAL_EXPIRED,
            self::TRANSITION_PAID_EXPIRED,
        ], true)) {
            $reason = self::WEBSITE_REASON_SUBSCRIPTION_REQUIRED;
        }

        return [
            'status' => self::WEBSITE_RESTRICTED,
            'allowed' => false,
            'reason' => $reason,
        ];
    }

    protected function resolveOwner(User $user): User
    {
        return method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
    }

    protected function daysRemaining(Membership $membership): ?int
    {
        if (!$membership->expire_date) {
            return null;
        }

        $expireDate = Carbon::parse($membership->expire_date);
        if ($expireDate->year >= 9999) {
            return null;
        }

        return max(0, Carbon::today()->diffInDays($expireDate, false));
    }

    protected function toDateString($value): ?string
    {
        if (!$value) {
            return null;
        }

        return Carbon::parse($value)->toDateString();
    }
}
