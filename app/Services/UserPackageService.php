<?php

namespace App\Services;

use Session;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use Illuminate\Http\Request;
use App\Http\Helpers\UserPermissionHelper;
use Illuminate\Support\Facades\Log;

class UserPackageService
{
    public function changeCurrentPackage(Request $request)
    {
        $membershipService = app(MembershipService::class);
        $userId = (int) $request->user_id;
        $user = User::findOrFail($userId);
        $currMembership = UserPermissionHelper::currMembOrPending($userId);
        $nextMembership = UserPermissionHelper::nextMembership($userId);

        if (!$currMembership) {
            Session::flash('membership_warning', 'No current package found for this user. Use Add Package instead.');
            return back();
        }

        $selectedPackage = Package::find($request->package_id);
        if (!$selectedPackage) {
            Session::flash('membership_warning', 'Selected package not found.');
            return back();
        }

        if (!empty($nextMembership) && $selectedPackage->term === MembershipService::TERM_LIFETIME) {
            Session::flash('membership_warning', 'To add a Lifetime package as Current Package, You have to remove the next package');
            return back();
        }

        $membershipService->activateImmediateMembership($user, $selectedPackage, [
            'payment_method' => $request->payment_method,
            'transaction_id' => uniqid(),
            'source' => 'admin_change_current',
        ]);

        if (!empty($nextMembership) && $selectedPackage->term !== MembershipService::TERM_LIFETIME) {
            $this->recalculateQueuedNextMembership($nextMembership, $membershipService);
        }

        Session::flash('success', 'Current Package changed successfully!');
        return back();
    }

    public function addCurrentPackage(Request $request)
    {
        $membershipService = app(MembershipService::class);
        $userId = (int) $request->user_id;
        $user = User::findOrFail($userId);
        $selectedPackage = Package::find($request->package_id);

        if (!$selectedPackage) {
            Session::flash('membership_warning', 'Selected package not found.');
            return back();
        }

        $skipHooks = $selectedPackage->id == MembershipService::FREE_PACKAGE_ID
            && $request->payment_method == 'system';

        if ($skipHooks) {
            $expiredTrialMembership = Membership::where('user_id', $userId)
                ->where('package_id', MembershipService::TRIAL_PACKAGE_ID)
                ->where('status', 1)
                ->whereDate('expire_date', '<', now()->toDateString())
                ->orderByDesc('expire_date')
                ->first();

            if ($expiredTrialMembership) {
                Log::info('Trial to Free Package Switch: User ID ' . $userId . ' switched from trial to free with 1-year expiry');
            }
        }

        $membershipService->activateImmediateMembership($user, $selectedPackage, [
            'payment_method' => $request->payment_method,
            'transaction_id' => uniqid(),
            'source' => 'admin_add_current',
            'skip_upgrade_hooks' => $skipHooks,
        ]);

        Session::flash('success', 'Current Package has been added successfully!');
        return back();
    }

    private function recalculateQueuedNextMembership(Membership $nextMembership, MembershipService $membershipService): void
    {
        $nextPackage = Package::find($nextMembership->package_id);
        if (!$nextPackage) {
            return;
        }

        $newMembership = Membership::query()
            ->where('user_id', $nextMembership->user_id)
            ->where('status', 1)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('expire_date', '>=', now()->toDateString())
            ->orderByDesc('id')
            ->first();

        if (!$newMembership) {
            return;
        }

        $nextStart = Carbon::parse($newMembership->expire_date)->addDay();
        $nextMembership->start_date = $nextStart->format('Y-m-d');
        $nextMembership->expire_date = $membershipService
            ->calculateExpireDate($nextPackage, $nextStart, 1)
            ->format('Y-m-d');
        $nextMembership->save();
    }
}
