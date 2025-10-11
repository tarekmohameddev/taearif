<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Package;
use App\Services\MembershipService;
use App\Models\Api\GeneralSetting;
use Illuminate\Support\Facades\Log;

class TestPackageUpgrade extends Command
{
    protected $signature = 'test:package-upgrade {user_id} {package_id}';
    protected $description = 'Test handlePackageUpgrade method directly';

    protected $membershipService;

    public function __construct(MembershipService $membershipService)
    {
        parent::__construct();
        $this->membershipService = $membershipService;
    }

    public function handle()
    {
        $userId = $this->argument('user_id');
        $packageId = $this->argument('package_id');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found!");
            return 1;
        }

        $package = Package::find($packageId);
        if (!$package) {
            $this->error("Package not found!");
            return 1;
        }

        $this->info("Testing handlePackageUpgrade:");
        $this->info("User: {$user->username} (ID: {$userId})");
        $this->info("Package: {$package->title} (ID: {$packageId})");

        // Check maintenance mode BEFORE
        $maintenanceBefore = $this->membershipService->isMaintenanceModeEnabled($user);
        $this->info("\nMaintenance Mode BEFORE: " . ($maintenanceBefore ? 'ENABLED' : 'DISABLED'));

        try {
            // Call handlePackageUpgrade
            $this->info("\nCalling handlePackageUpgrade...");
            $this->membershipService->handlePackageUpgrade($user, $packageId, 'test_command');
            $this->info("✅ Method called successfully!");

            // Check maintenance mode AFTER
            $user->refresh();
            $maintenanceAfter = $this->membershipService->isMaintenanceModeEnabled($user);
            $this->info("\nMaintenance Mode AFTER: " . ($maintenanceAfter ? 'ENABLED' : 'DISABLED'));

            if ($maintenanceBefore && !$maintenanceAfter) {
                $this->info("\n✅ SUCCESS: Maintenance mode was disabled!");
            } elseif ($maintenanceBefore && $maintenanceAfter) {
                $this->warn("\n⚠️  Maintenance mode is still enabled (may be expected behavior)");
            } else {
                $this->info("\n✅ Maintenance mode status unchanged");
            }

            // Check logs
            $this->info("\nCheck storage/logs/laravel.log for detailed logging");

            return 0;

        } catch (\Exception $e) {
            $this->error("\n❌ ERROR: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}


