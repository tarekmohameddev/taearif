<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Membership;
use App\Services\MembershipService;
use Carbon\Carbon;

class ForceExpireMembership extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:force-expire {user_id : The user ID to expire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force expire a user membership (for testing) - downgrades to free package and enables maintenance mode';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MembershipService $membershipService)
    {
        $userId = $this->argument('user_id');
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🔴 FORCE EXPIRE MEMBERSHIP - Testing Mode');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Find user
        $user = User::find($userId);
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found!");
            return 1;
        }

        $this->info("👤 User: {$user->username} (ID: {$userId})");
        $this->info("📧 Email: {$user->email}");
        $this->newLine();

        // Show current status
        $this->info('📊 CURRENT STATUS:');
        $this->line('─────────────────────────────────────────────────');
        
        $currentMembership = Membership::where('user_id', $userId)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($currentMembership) {
            $package = $currentMembership->package;
            $packageTitle = $package ? $package->title : 'Unknown';
            $this->info("  Current Package: {$packageTitle} (ID: {$currentMembership->package_id})");
            $this->info("  Expires: {$currentMembership->expire_date}");
            $this->info("  Status: " . ($currentMembership->status == 1 ? 'Active' : 'Inactive'));
        } else {
            $this->warn('  No active membership found');
        }
        
        $maintenanceMode = $membershipService->isMaintenanceModeEnabled($user);
        $this->info("  Maintenance Mode: " . ($maintenanceMode ? '🔴 Enabled' : '🟢 Disabled'));
        $this->newLine();

        // Confirm action
        if (!$this->confirm('⚠️  This will expire the user membership and enable maintenance mode. Continue?', true)) {
            $this->warn('❌ Operation cancelled');
            return 0;
        }

        $this->newLine();
        $this->info('🔄 Processing expiration...');

        // Step 1: Expire current memberships
        if ($currentMembership) {
            $currentMembership->expire_date = Carbon::now()->subDay();
            $currentMembership->save();
            $this->info('  ✅ Current membership set to expired');
        }

        // Step 2: Run expiration handler (downgrades to free + enables maintenance)
        $membershipService->handleMembershipExpiration($user);
        $this->info('  ✅ Expiration handler executed');

        $this->newLine();

        // Show new status
        $user->refresh();
        $this->info('📊 NEW STATUS:');
        $this->line('─────────────────────────────────────────────────');
        
        $isFree = $membershipService->hasFreePackage($user);
        $maintenanceEnabled = $membershipService->isMaintenanceModeEnabled($user);
        $canControl = $membershipService->canControlMaintenanceMode($user);
        
        $this->table(
            ['Status', 'Value'],
            [
                ['Package', $isFree ? '✅ Free Package (ID: 16)' : '⚠️ Other Package'],
                ['Maintenance Mode', $maintenanceEnabled ? '🔴 Enabled' : '🟢 Disabled'],
                ['Can Control Maintenance', $canControl ? '✅ Yes' : '❌ No'],
            ]
        );

        $this->newLine();
        
        if ($isFree && $maintenanceEnabled && !$canControl) {
            $this->info('🎉 SUCCESS! User membership expired:');
            $this->info('   ✅ Downgraded to Free Package');
            $this->info('   ✅ Maintenance mode enabled');
            $this->info('   ✅ User cannot disable maintenance mode');
        } else {
            $this->warn('⚠️  Expiration completed but status may not be as expected');
        }

        $this->newLine();
        $this->info('💡 To test renewal, run:');
        $this->line("   php artisan membership:force-renew {$userId}");
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }
}

