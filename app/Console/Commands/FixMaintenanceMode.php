<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Api\GeneralSetting;
use App\Services\MembershipService;
use App\Http\Helpers\UserPermissionHelper;
use Illuminate\Support\Facades\Log;

class FixMaintenanceMode extends Command
{
    protected $signature = 'maintenance:fix {user_id}';
    protected $description = 'Fix maintenance mode for a specific user who upgraded from free to paid package';

    protected $membershipService;

    public function __construct(MembershipService $membershipService)
    {
        parent::__construct();
        $this->membershipService = $membershipService;
    }

    public function handle()
    {
        $userId = $this->argument('user_id');

        $this->info("Checking user ID: {$userId}");

        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }

        $this->info("User: {$user->username} ({$user->email})");

        // Check current package
        $currentPackage = UserPermissionHelper::userPackage($userId);
        if ($currentPackage) {
            $this->info("Current Package ID: {$currentPackage->id}");
            $this->info("Package Term: {$currentPackage->term}");
        } else {
            $this->warn("No current package found");
        }

        // Check maintenance mode status
        $maintenanceEnabled = $this->membershipService->isMaintenanceModeEnabled($user);
        $this->info("Maintenance Mode: " . ($maintenanceEnabled ? 'ENABLED ❌' : 'DISABLED ✅'));

        // Check if user can control maintenance mode
        $canControl = $this->membershipService->canControlMaintenanceMode($user);
        $this->info("Can Control Maintenance: " . ($canControl ? 'YES ✅' : 'NO ❌'));

        // Check if user is on free or paid package
        $isFree = $this->membershipService->hasFreePackage($user);
        $isTrial = $this->membershipService->hasTrialPackage($user);
        $isPaid = $this->membershipService->hasPaidPackage($user);

        $this->info("Package Type:");
        $this->info("  - Free: " . ($isFree ? 'YES' : 'NO'));
        $this->info("  - Trial: " . ($isTrial ? 'YES' : 'NO'));
        $this->info("  - Paid: " . ($isPaid ? 'YES' : 'NO'));

        // If user is on paid package but maintenance is enabled, fix it
        if ($maintenanceEnabled && !$isFree && !$isTrial) {
            $this->warn("\n⚠️  User is on paid package but maintenance mode is ENABLED!");

            if ($this->confirm('Do you want to disable maintenance mode now?', true)) {
                $this->info("\nDisabling maintenance mode...");
                $this->membershipService->disableMaintenanceMode($user);

                // Verify it worked
                $stillEnabled = $this->membershipService->isMaintenanceModeEnabled($user);
                if (!$stillEnabled) {
                    $this->info("✅ Maintenance mode DISABLED successfully!");

                    // Log the fix
                    Log::info("Manual maintenance mode fix applied for user {$userId} via console command");

                    return 0;
                } else {
                    $this->error("❌ Failed to disable maintenance mode!");
                    return 1;
                }
            }
        } elseif (!$maintenanceEnabled && !$isFree && !$isTrial) {
            $this->info("\n✅ Everything is correct! User is on paid package and maintenance mode is disabled.");
            return 0;
        } elseif ($maintenanceEnabled && ($isFree || $isTrial)) {
            $this->info("\n✅ Everything is correct! User is on free/trial package and maintenance mode is enabled.");
            return 0;
        }

        return 0;
    }
}


