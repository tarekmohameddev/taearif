<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Services\MembershipService;
use Carbon\Carbon;

class ForceRenewMembership extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:force-renew 
                            {user_id : The user ID to renew}
                            {package_id? : Package ID to upgrade to (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force renew a user membership (for testing) - upgrades to paid package and disables maintenance mode';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MembershipService $membershipService)
    {
        $userId = $this->argument('user_id');
        $packageId = $this->argument('package_id');
        
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('🟢 FORCE RENEW MEMBERSHIP - Testing Mode');
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
        
        $isFree = $membershipService->hasFreePackage($user);
        $maintenanceEnabled = $membershipService->isMaintenanceModeEnabled($user);
        
        $this->table(
            ['Status', 'Value'],
            [
                ['Package', $isFree ? '🔴 Free Package' : '✅ Paid Package'],
                ['Maintenance Mode', $maintenanceEnabled ? '🔴 Enabled' : '🟢 Disabled'],
            ]
        );
        $this->newLine();

        // Select package
        if (!$packageId) {
            $this->info('📦 Available Packages:');
            $packages = Package::where('status', 1)
                ->whereNotIn('id', [16, 26]) // Exclude free and trial
                ->get(['id', 'title', 'term', 'price']);
            
            if ($packages->isEmpty()) {
                $this->error('❌ No paid packages found in database!');
                $this->warn('💡 Using fallback package ID: 5');
                $packageId = 5;
            } else {
                $this->table(
                    ['ID', 'Package Name', 'Term', 'Price'],
                    $packages->map(fn($p) => [$p->id, $p->title, $p->term, $p->price])
                );
                
                $packageId = $this->ask('Enter Package ID to upgrade to', $packages->first()->id);
            }
        }

        // Verify package exists
        $package = Package::find($packageId);
        if (!$package) {
            $this->error("❌ Package with ID {$packageId} not found!");
            return 1;
        }

        $this->newLine();
        $this->info("📦 Selected Package: {$package->title} (ID: {$packageId})");
        $this->info("   Term: {$package->term}");
        $this->info("   Price: {$package->price}");
        $this->newLine();

        // Confirm action
        if (!$this->confirm('✅ This will upgrade the user to this package and disable maintenance mode. Continue?', true)) {
            $this->warn('❌ Operation cancelled');
            return 0;
        }

        $this->newLine();
        $this->info('🔄 Processing renewal...');

        // Calculate expiry date
        $expiryDate = $membershipService->calculateExpireDate($package, Carbon::now());

        // Step 1: Create new membership
        $newMembership = Membership::create([
            'user_id' => $userId,
            'package_id' => $packageId,
            'status' => 1,
            'start_date' => Carbon::now(),
            'expire_date' => $expiryDate,
            'price' => $package->price,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'test_renewal',
            'transaction_id' => 'RENEW_TEST_' . time(),
        ]);
        $this->info('  ✅ New membership created');

        // Step 2: Expire old free package membership
        $freeMembership = Membership::where('user_id', $userId)
            ->where('package_id', 16)
            ->where('status', 1)
            ->where('id', '!=', $newMembership->id)
            ->first();
        
        if ($freeMembership) {
            $freeMembership->expire_date = Carbon::now()->subDay();
            $freeMembership->save();
            $this->info('  ✅ Old free package membership expired');
        }

        // Step 3: Handle package upgrade (auto-disable maintenance mode)
        $membershipService->handlePackageUpgrade($user, $packageId, 'test_command');
        $this->info('  ✅ Package upgrade handler executed');

        $this->newLine();

        // Show new status
        $user->refresh();
        $this->info('📊 NEW STATUS:');
        $this->line('─────────────────────────────────────────────────');
        
        $isFreeNow = $membershipService->hasFreePackage($user);
        $maintenanceEnabledNow = $membershipService->isMaintenanceModeEnabled($user);
        $canControl = $membershipService->canControlMaintenanceMode($user);
        
        $status = $membershipService->getMembershipStatus($user);
        
        $packageName = isset($status['package_name']) ? $status['package_name'] : 'Unknown';
        $packageTerm = isset($status['package_term']) ? $status['package_term'] : 'Unknown';
        $expiresAt = isset($status['expires_at']) ? $status['expires_at'] : 'Unknown';
        
        $this->table(
            ['Status', 'Value'],
            [
                ['Package', $packageName],
                ['Package Term', $packageTerm],
                ['Expires At', $expiresAt],
                ['Is Free', $isFreeNow ? '🔴 Yes' : '✅ No'],
                ['Maintenance Mode', $maintenanceEnabledNow ? '🔴 Enabled' : '🟢 Disabled'],
                ['Can Control Maintenance', $canControl ? '✅ Yes' : '❌ No'],
            ]
        );

        $this->newLine();
        
        if (!$isFreeNow && !$maintenanceEnabledNow && $canControl) {
            $this->info('🎉 SUCCESS! User membership renewed:');
            $this->info('   ✅ Upgraded to paid package');
            $this->info('   ✅ Maintenance mode disabled');
            $this->info('   ✅ User can control maintenance mode');
        } else {
            $this->warn('⚠️  Renewal completed but status may not be as expected');
        }

        $this->newLine();
        $this->info('💡 To test expiration again, run:');
        $this->line("   php artisan membership:force-expire {$userId}");
        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        return 0;
    }
}

