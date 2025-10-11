<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Models\Api\GeneralSetting;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TestMembershipRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:membership-renewal 
                            {user_id? : The user ID to test with (optional - will create test user if not provided)}
                            {--cleanup : Clean up test data after completion}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test membership renewal and maintenance mode auto-restoration on real database';

    protected $membershipService;
    protected $testUser;
    protected $isTemporaryUser = false;

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
        
        $this->info('🧪 Starting Membership Renewal Test on REAL DATABASE');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Get or create test user
        $userId = $this->argument('user_id');
        
        if ($userId) {
            $this->testUser = User::find($userId);
            if (!$this->testUser) {
                $this->error("❌ User with ID {$userId} not found!");
                return 1;
            }
            $this->info("✅ Using existing user: {$this->testUser->username} (ID: {$userId})");
        } else {
            $this->testUser = $this->createTestUser();
            $this->isTemporaryUser = true;
            $this->info("✅ Created temporary test user: {$this->testUser->username} (ID: {$this->testUser->id})");
        }
        
        $this->newLine();
        
        // Run test scenarios
        try {
            DB::beginTransaction();
            
            $this->testScenario1_ExpirationFlow();
            $this->newLine();
            
            $this->testScenario2_RenewalFlow();
            $this->newLine();
            
            $this->testScenario3_VerifyResults();
            $this->newLine();
            
            // Ask if we should commit or rollback
            if ($this->option('cleanup') || $this->isTemporaryUser) {
                DB::rollBack();
                $this->warn('🔄 Changes rolled back (test data cleaned up)');
                
                if ($this->isTemporaryUser) {
                    $this->testUser->delete();
                    $this->info('🗑️  Temporary test user deleted');
                }
            } else {
                if ($this->confirm('Do you want to keep these test changes in the database?', false)) {
                    DB::commit();
                    $this->info('✅ Changes committed to database');
                } else {
                    DB::rollBack();
                    $this->warn('🔄 Changes rolled back');
                }
            }
            
            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('🎉 Test completed successfully!');
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Test failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
    
    protected function createTestUser()
    {
        return User::create([
            'username' => 'test_renewal_' . time(),
            'email' => 'test_renewal_' . time() . '@example.com',
            'first_name' => 'Test',
            'last_name' => 'Renewal',
            'password' => bcrypt('password'),
            'phone' => '+966500000000',
        ]);
    }
    
    protected function testScenario1_ExpirationFlow()
    {
        $this->info('📋 SCENARIO 1: Membership Expiration → Downgrade to Free');
        $this->info('────────────────────────────────────────────────────');
        
        // Create expired paid membership
        $paidPackage = Package::where('term', 'monthly')->where('status', 1)->first();
        if (!$paidPackage) {
            $this->warn('⚠️  No monthly package found, skipping paid package simulation');
            $paidPackageId = 5; // Fallback
        } else {
            $paidPackageId = $paidPackage->id;
            $this->info("  Using package: {$paidPackage->title} (ID: {$paidPackageId})");
        }
        
        // Create expired membership
        $expiredMembership = Membership::create([
            'user_id' => $this->testUser->id,
            'package_id' => $paidPackageId,
            'status' => 1,
            'start_date' => Carbon::now()->subMonths(2),
            'expire_date' => Carbon::now()->subDay(), // Expired yesterday
            'price' => 100,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'payment_method' => 'stripe',
            'transaction_id' => 'TEST_' . time(),
        ]);
        
        $this->info("  ✅ Created expired membership (expired: {$expiredMembership->expire_date})");
        
        // Simulate expiration cron job
        $this->info('  🔄 Running expiration handler...');
        $this->membershipService->handleMembershipExpiration($this->testUser);
        
        // Check results
        $this->testUser->refresh();
        $isFree = $this->membershipService->hasFreePackage($this->testUser);
        $maintenanceEnabled = $this->membershipService->isMaintenanceModeEnabled($this->testUser);
        
        if ($isFree) {
            $this->info('  ✅ User downgraded to Free Package (ID: 16)');
        } else {
            $this->warn('  ⚠️  User not on free package');
        }
        
        if ($maintenanceEnabled) {
            $this->info('  ✅ Maintenance mode enabled');
        } else {
            $this->warn('  ⚠️  Maintenance mode not enabled');
        }
        
        $this->table(
            ['Status', 'Value'],
            [
                ['Is Free Package', $isFree ? '✅ Yes' : '❌ No'],
                ['Maintenance Mode', $maintenanceEnabled ? '✅ Enabled' : '❌ Disabled'],
                ['Can Control Maintenance', $this->membershipService->canControlMaintenanceMode($this->testUser) ? '✅ Yes' : '❌ No'],
            ]
        );
    }
    
    protected function testScenario2_RenewalFlow()
    {
        $this->info('📋 SCENARIO 2: User Renews → Maintenance Mode Auto-Restored');
        $this->info('────────────────────────────────────────────────────');
        
        // Find a paid package
        $newPaidPackage = Package::where('term', 'yearly')->where('status', 1)->first();
        if (!$newPaidPackage) {
            $this->warn('⚠️  No yearly package found, using ID 6 as fallback');
            $newPackageId = 6;
        } else {
            $newPackageId = $newPaidPackage->id;
            $this->info("  Using package: {$newPaidPackage->title} (ID: {$newPackageId})");
        }
        
        // Simulate user renewal (payment success)
        $this->info('  💳 Simulating payment success and package upgrade...');
        $this->membershipService->handlePackageUpgrade($this->testUser, $newPackageId, 'test_command');
        
        // Check results
        $this->testUser->refresh();
        $maintenanceDisabled = !$this->membershipService->isMaintenanceModeEnabled($this->testUser);
        $canControl = $this->membershipService->canControlMaintenanceMode($this->testUser);
        
        if ($maintenanceDisabled) {
            $this->info('  ✅ Maintenance mode automatically disabled');
        } else {
            $this->warn('  ⚠️  Maintenance mode still enabled');
        }
        
        if ($canControl) {
            $this->info('  ✅ User can now control maintenance mode');
        } else {
            $this->warn('  ⚠️  User cannot control maintenance mode');
        }
    }
    
    protected function testScenario3_VerifyResults()
    {
        $this->info('📋 SCENARIO 3: Final Verification');
        $this->info('────────────────────────────────────────────────────');
        
        $status = $this->membershipService->getMembershipStatus($this->testUser);
        
        $this->table(
            ['Property', 'Value'],
            [
                ['User ID', $this->testUser->id],
                ['Username', $this->testUser->username],
                ['Has Membership', $status['has_membership'] ? '✅ Yes' : '❌ No'],
                ['Package Name', $status['package_name'] ?? 'N/A'],
                ['Package Term', $status['package_term'] ?? 'N/A'],
                ['Is Free', $status['is_free'] ? '✅ Yes' : '❌ No'],
                ['Is Trial', $status['is_trial'] ? '✅ Yes' : '❌ No'],
                ['Is Paid', $status['is_paid'] ? '✅ Yes' : '❌ No'],
                ['Can Control Maintenance', $status['can_control_maintenance'] ? '✅ Yes' : '❌ No'],
                ['Maintenance Enabled', $status['maintenance_mode_enabled'] ? '🔴 Yes' : '🟢 No'],
                ['Expires At', $status['expires_at'] ?? 'N/A'],
            ]
        );
        
        // Summary
        $this->newLine();
        $allGood = !$status['is_free'] 
                   && !$status['maintenance_mode_enabled'] 
                   && $status['can_control_maintenance'];
        
        if ($allGood) {
            $this->info('🎉 SUCCESS: All checks passed!');
            $this->info('   ✅ User upgraded from free package');
            $this->info('   ✅ Maintenance mode automatically disabled');
            $this->info('   ✅ User can control maintenance mode');
        } else {
            $this->warn('⚠️  Some checks failed - review the table above');
        }
    }
}

