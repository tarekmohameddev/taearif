<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Services\Membership\MembershipAccessStateService;
use App\Services\MembershipService;
use App\Support\CacheInvalidationHelper;
use Illuminate\Console\Command;

class BackfillMembershipTransitionMetadata extends Command
{
    protected $signature = 'subscription:backfill-transition-metadata
        {--dry-run : Preview updates without writing}
        {--apply : Persist updates}
        {--user-id= : Limit processing to one user}
        {--chunk=200 : Chunk size}';

    protected $description = 'Backfill unambiguous free-membership expiration transition metadata';

    public function handle(MembershipAccessStateService $accessStateService): int
    {
        $apply = (bool) $this->option('apply');
        $dryRun = !$apply || (bool) $this->option('dry-run');
        $freePackageId = (int) config('membership.free_package_id', MembershipService::FREE_PACKAGE_ID);
        $chunk = max(1, (int) $this->option('chunk'));

        $counts = [
            'scanned' => 0,
            'updated_trial' => 0,
            'updated_paid' => 0,
            'skipped_ambiguous' => 0,
            'skipped_populated' => 0,
        ];

        $query = Membership::query()
            ->with('package')
            ->where('package_id', $freePackageId)
            ->whereNull('activation_source')
            ->whereNull('transition_reason')
            ->orderBy('id');

        if ($this->option('user-id')) {
            $query->where('user_id', (int) $this->option('user-id'));
        }

        $query->chunkById($chunk, function ($memberships) use ($accessStateService, &$counts, $dryRun, $freePackageId) {
            foreach ($memberships as $membership) {
                $counts['scanned']++;

                if (!empty($membership->activation_source) || !empty($membership->transition_reason)) {
                    $counts['skipped_populated']++;
                    continue;
                }

                if ((string) $membership->payment_method !== 'system') {
                    $counts['skipped_ambiguous']++;
                    continue;
                }

                $previousMembership = Membership::query()
                    ->with('package')
                    ->where('user_id', $membership->user_id)
                    ->where('id', '<', $membership->id)
                    ->orderByDesc('id')
                    ->first();

                if (!$previousMembership || (int) $previousMembership->package_id === $freePackageId) {
                    $counts['skipped_ambiguous']++;
                    continue;
                }

                $interveningExists = Membership::query()
                    ->where('user_id', $membership->user_id)
                    ->where('id', '>', $previousMembership->id)
                    ->where('id', '<', $membership->id)
                    ->exists();

                if ($interveningExists || !$previousMembership->expire_date || !$membership->start_date) {
                    $counts['skipped_ambiguous']++;
                    continue;
                }

                if ($previousMembership->expire_date > $membership->start_date) {
                    $counts['skipped_ambiguous']++;
                    continue;
                }

                $previousPlanType = $accessStateService->classifyPackage($previousMembership, $previousMembership->package);
                if ($previousPlanType === MembershipAccessStateService::PLAN_TRIAL) {
                    $transitionReason = MembershipAccessStateService::TRANSITION_TRIAL_EXPIRED;
                } elseif ($previousPlanType === MembershipAccessStateService::PLAN_PAID) {
                    $transitionReason = MembershipAccessStateService::TRANSITION_PAID_EXPIRED;
                } else {
                    $counts['skipped_ambiguous']++;
                    continue;
                }

                if (!$dryRun) {
                    $membership->forceFill([
                        'activation_source' => 'expiration_fallback',
                        'transition_reason' => $transitionReason,
                        'previous_membership_id' => $previousMembership->id,
                    ])->save();

                    CacheInvalidationHelper::clearTenantProfileCachesAuto((int) $membership->user_id);
                }

                if ($transitionReason === MembershipAccessStateService::TRANSITION_TRIAL_EXPIRED) {
                    $counts['updated_trial']++;
                } else {
                    $counts['updated_paid']++;
                }
            }
        });

        $mode = $dryRun ? 'DRY RUN' : 'APPLY';
        $this->info("Mode: {$mode}");
        $this->line('scanned=' . $counts['scanned']);
        $this->line('updated_trial=' . $counts['updated_trial']);
        $this->line('updated_paid=' . $counts['updated_paid']);
        $this->line('skipped_ambiguous=' . $counts['skipped_ambiguous']);
        $this->line('skipped_already_populated=' . $counts['skipped_populated']);

        return self::SUCCESS;
    }
}
