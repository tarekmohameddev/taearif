<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Models\Api\GeneralSetting;
use App\Models\BasicExtended;
use App\Services\MembershipService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SimulateArbPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simulate:arb-payment {user_id} {package_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate ARB payment success for a user to upgrade from free to paid package';

    protected $membershipService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(MembershipService $membershipService)
    {
        parent::__construct();
        $this->membershipService = $membershipService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $packageId = $this->argument('package_id');

        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  ARB Payment Simulation Started");
        $this->info("═══════════════════════════════════════════════════════");
        $this->newLine();

        // Step 1: Verify user exists
        $this->info("📋 Step 1: Verifying User...");
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ User with ID {$userId} not found!");
            return 1;
        }
        
        $this->info("✅ User found: {$user->username} ({$user->email})");
        Log::info("ARB Payment Simulation - User verified", ['user_id' => $userId, 'username' => $user->username]);
        $this->newLine();

        // Step 2: Verify package exists
        $this->info("📋 Step 2: Verifying Package...");
        $package = Package::find($packageId);
        
        if (!$package) {
            $this->error("❌ Package with ID {$packageId} not found!");
            return 1;
        }
        
        if (!in_array($packageId, [24, 25])) {
            $this->warn("⚠️  Warning: Package ID {$packageId} is not 24 or 25 (expected paid packages)");
        }
        
        $this->info("✅ Package found: {$package->title}");
        $this->info("   - Price: {$package->price}");
        $this->info("   - Term: {$package->term}");
        Log::info("ARB Payment Simulation - Package verified", ['package_id' => $packageId, 'title' => $package->title]);
        $this->newLine();

        // Step 3: Show BEFORE state
        $this->info("📋 Step 3: Current State (BEFORE)...");
        $this->showCurrentState($user);
        $this->newLine();

        // Step 4: Simulate ARB Payment Success
        $this->info("📋 Step 4: Simulating ARB Payment Success...");
        
        try {
            DB::beginTransaction();

            $be = BasicExtended::first();
            if (!$be) {
                $this->error("❌ BasicExtended settings not found!");
                return 1;
            }

            // Calculate dates based on package term
            $startDate = Carbon::now();
            if ($package->term == 'monthly') {
                $expireDate = Carbon::now()->addMonth();
            } elseif ($package->term == 'yearly') {
                $expireDate = Carbon::now()->addYear();
            } else {
                $expireDate = Carbon::maxValue();
            }

            // Generate transaction details
            $transactionId = 'ARB_SIM_' . uniqid() . '_' . time();
            $transactionDetails = json_encode([
                'simulation' => true,
                'gateway' => 'ARB',
                'timestamp' => now()->toDateTimeString(),
                'user_id' => $userId,
                'package_id' => $packageId,
            ]);

            $this->info("💳 Payment Details:");
            $this->info("   - Transaction ID: {$transactionId}");
            $this->info("   - Amount: {$package->price}");
            $this->info("   - Currency: {$be->base_currency_text}");
            $this->info("   - Start Date: {$startDate->format('Y-m-d')}");
            $this->info("   - Expire Date: {$expireDate->format('Y-m-d')}");
            
            Log::info("ARB Payment Simulation - Creating membership", [
                'user_id' => $userId,
                'package_id' => $packageId,
                'transaction_id' => $transactionId,
                'start_date' => $startDate->format('Y-m-d'),
                'expire_date' => $expireDate->format('Y-m-d'),
            ]);

            // Step 5: Create membership record (same as real payment)
            $this->info("📝 Creating Membership Record...");
            
            $membership = Membership::create([
                'package_price' => $package->price,
                'discount' => 0,
                'coupon_code' => null,
                'price' => $package->price,
                'currency' => $be->base_currency_text ?? 'USD',
                'currency_symbol' => $be->base_currency_symbol ?? '$',
                'payment_method' => 'ARB (Simulated)',
                'transaction_id' => $transactionId,
                'status' => 1, // Active
                'is_trial' => 0,
                'trial_days' => 0,
                'receipt' => null,
                'transaction_details' => $transactionDetails,
                'settings' => json_encode($be),
                'package_id' => $packageId,
                'user_id' => $userId,
                'start_date' => $startDate,
                'expire_date' => $expireDate,
                'conversation_id' => null,
            ]);

            $this->info("✅ Membership created with ID: {$membership->id}");
            Log::info("ARB Payment Simulation - Membership created", ['membership_id' => $membership->id]);

            // Step 6: Trigger package upgrade (this disables maintenance mode)
            $this->info("🔧 Triggering Package Upgrade Handler...");
            Log::info("ARB Payment Simulation - Calling handlePackageUpgrade");
            
            $this->membershipService->handlePackageUpgrade($user, $packageId, 'arb_simulation');
            
            $this->info("✅ Package upgrade handler completed");
            Log::info("ARB Payment Simulation - handlePackageUpgrade completed");

            DB::commit();
            $this->info("✅ Transaction committed successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error during simulation: " . $e->getMessage());
            Log::error("ARB Payment Simulation - Error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        $this->newLine();

        // Step 7: Show AFTER state
        $this->info("📋 Step 5: New State (AFTER)...");
        $this->showCurrentState($user);
        $this->newLine();

        // Step 8: Summary
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("  ✅ ARB Payment Simulation Completed Successfully!");
        $this->info("═══════════════════════════════════════════════════════");
        $this->info("📊 Summary:");
        $this->info("   - User ID: {$userId}");
        $this->info("   - Package: {$package->title} (ID: {$packageId})");
        $this->info("   - Transaction ID: {$transactionId}");
        $this->info("   - Membership ID: {$membership->id}");
        $this->newLine();
        $this->info("🎉 The user's website should now be accessible!");
        $this->info("🔍 Check logs for detailed execution trace.");
        $this->newLine();

        Log::info("ARB Payment Simulation - Completed successfully", [
            'user_id' => $userId,
            'package_id' => $packageId,
            'membership_id' => $membership->id,
        ]);

        return 0;
    }

    /**
     * Show current state of user
     */
    private function showCurrentState(User $user)
    {
        // Get current membership
        $currentMembership = Membership::where('user_id', $user->id)
            ->where('status', 1)
            ->where('start_date', '<=', now()->format('Y-m-d'))
            ->where('expire_date', '>=', now()->format('Y-m-d'))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($currentMembership) {
            $currentPackage = Package::find($currentMembership->package_id);
            $this->info("   📦 Current Package: {$currentPackage->title} (ID: {$currentMembership->package_id})");
            $this->info("   📅 Expires: {$currentMembership->expire_date}");
            $this->info("   💰 Price: {$currentMembership->price} {$currentMembership->currency}");
        } else {
            $this->warn("   📦 No active membership found");
        }

        // Get maintenance mode status
        $generalSettings = GeneralSetting::where('user_id', $user->id)->first();
        
        if ($generalSettings) {
            $maintenanceStatus = $generalSettings->maintenance_mode == 1 ? '🔴 ENABLED' : '🟢 DISABLED';
            $this->info("   🔧 Maintenance Mode: {$maintenanceStatus}");
            
            if ($generalSettings->maintenance_mode == 1) {
                $this->warn("   ⚠️  Website is NOT accessible to visitors");
            } else {
                $this->info("   ✅ Website IS accessible to visitors");
            }
        } else {
            $this->warn("   🔧 Maintenance Mode: Not set (no general_settings record)");
        }

        // Check if user can control maintenance mode
        $canControl = $this->membershipService->canControlMaintenanceMode($user);
        $controlStatus = $canControl ? '✅ YES' : '❌ NO (Free Package)';
        $this->info("   🎛️  Can Control Maintenance: {$controlStatus}");

        Log::info("ARB Payment Simulation - Current state", [
            'user_id' => $user->id,
            'current_package_id' => $currentMembership ? $currentMembership->package_id : null,
            'maintenance_mode' => $generalSettings ? $generalSettings->maintenance_mode : null,
            'can_control_maintenance' => $canControl,
        ]);
    }
}

