<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User\UserPermission;
use App\Models\User\UserPaymentGeteway;
use App\Models\User\UserEmailTemplate;
use App\Models\User\UserShopSetting;
use App\Models\User\SEO;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpiredUserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating test user with expired membership...');

        // Get the trial package (Package 26 with 7 days trial)
        $trialPackage = Package::find(26);
        if (!$trialPackage) {
            $this->command->error('Trial package (ID: 26) not found!');
            return;
        }

        // Get the free package (ID: 16 - الباقة-المجانية)
        $freePackage = Package::find(16);
        if (!$freePackage) {
            $this->command->error('Free package (ID: 16) not found!');
            return;
        }

        $this->command->info("Trial Package: {$trialPackage->title} (ID: {$trialPackage->id})");
        $this->command->info("Free Package: {$freePackage->title} (ID: {$freePackage->id})");

        // Create test user
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'ExpiredUser',
            'username' => 'test_expired_' . uniqid(),
            'email' => 'test_expired_' . uniqid() . '@example.com',
            'phone' => '1234567890',
            'password' => bcrypt('123123123'),
            'status' => 1,
            'message' => null,
            'address' => 'Test Address',
            'city' => 'Test City',
            'state' => 'Test State',
            'country' => 'Test Country',
            'tenant_id' => null,
        ]);

        $this->command->info("Created user: {$user->username} (ID: {$user->id})");

        // Create EXPIRED membership (expired 2 days ago)
        $expiredStartDate = Carbon::now()->subDays(9); // Started 9 days ago
        $expiredEndDate = Carbon::now()->subDays(2);   // Expired 2 days ago

        $expiredMembership = Membership::create([
            'user_id' => $user->id,
            'package_id' => $trialPackage->id,
            'package_price' => $trialPackage->price,
            'price' => $trialPackage->price,
            'currency' => 'SAR',
            'currency_symbol' => 'ر.س',
            'start_date' => $expiredStartDate->toDateString(),
            'expire_date' => $expiredEndDate->toDateString(),
            'status' => 1, // Active status (but expired)
            'modified' => 0,
            'payment_method' => 'trial',
            'transaction_id' => 'TEST_EXPIRED_' . uniqid(),
            'transaction_details' => 'Test Expired Trial',
            'is_trial' => 1,
            'trial_days' => $trialPackage->trial_days,
        ]);

        $this->command->info("Created EXPIRED membership:");
        $this->command->info("  - Package: {$trialPackage->title}");
        $this->command->info("  - Start Date: {$expiredStartDate->toDateString()}");
        $this->command->info("  - Expire Date: {$expiredEndDate->toDateString()} (EXPIRED 2 days ago)");
        $this->command->info("  - Status: {$expiredMembership->status}");

        // Create user permissions for the trial package
        UserPermission::create([
            'user_id' => $user->id,
            'package_id' => $trialPackage->id,
        ]);

        $this->command->info('');
        $this->command->info('✅ Test user with expired membership created successfully!');
        $this->command->info('');
        $this->command->info('User Details:');
        $this->command->info("  - Username: {$user->username}");
        $this->command->info("  - Email: {$user->email}");
        $this->command->info("  - Password: 123123123");
        $this->command->info("  - Membership: EXPIRED (Package: {$trialPackage->title})");
        $this->command->info('');
        $this->command->info('To test the downgrade:');
        $this->command->info('  php artisan expire:user');
        $this->command->info('');
        $this->command->info('Expected result:');
        $this->command->info("  - User should be downgraded to: {$freePackage->title}");
        $this->command->info('  - User should receive welcome message about free package');
    }
}
