<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Membership;
use App\Models\AffiliateTransaction;
use App\Models\Api\ApiAffiliateUser;
use Illuminate\Support\Str;

class SimulateAffiliatePaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // CHANGE THIS ID EACH TIME TO TEST NEW USER
        // $userId = 874; // ID of the user to simulate payment for
        // $userId = 832;
        // $userId = 831;
        // $userId = 830;
        // $userId = 829;
        // $referred_by = 833; // ID of the user who referred this user

        // $userId = 868;
        $userId = 867;
        $referred_by = 829; // ID of the user who referred this user

        $user = User::find($userId);

        if (!$user) {
            $this->command->error("User with ID $userId not found.");
            return;
        }

        // 1. Update user subscription status
        $user->subscription_amount = 299;
        $user->subscribed = true;
        $user->referred_by = $referred_by; // Set the user who referred this user
        $user->save();

        // Create simulated membership
        try {
            $membership = Membership::create([
                'user_id' => $user->id,
                'package_id' => 16,
                'price' => 299,
                'package_price' => 299,
                'currency' => 'SAR',
                'currency_symbol' => '﷼',
                'transaction_id' => Str::upper(Str::random(10)),
                'payment_method' => 'Manual',
                'is_trial' => false,
                'trial_days' => 0,
                'receipt' => null,
                'transaction_details' => null,
                'settings' => null,
                'discount' => 0,
                'coupon_code' => null,
                'modified'=> 0,
                'conversation_id' => null,
                'status' => 1,
                'start_date' => now(),
                'expire_date' => now()->addMonth(),
            ]);

            $this->command->info(" Membership created with ID: {$membership->id}");

        } catch (\Exception $e) {
            $this->command->error(" Failed to create membership: " . $e->getMessage());
            return;
        }

        // 3. Log affiliate commission
        $affiliate = ApiAffiliateUser::where('user_id', $user->referred_by)->first();

        if (!$affiliate) {
            $this->command->warn("No affiliate found for referral user ID: {$user->referred_by}");
            return;
        }

        $commissionRate = $affiliate->commission_percentage ?? 0.15;
        $commissionAmount = $user->subscription_amount * $commissionRate;
        // $affiliate->total_commission += $commissionAmount;
        $affiliate->pending_amount += $commissionAmount;

        // $affiliate->available_amount += $commissionAmount;
        // $affiliate->total_earned += $commissionAmount;
        // $affiliate->total_commission_pending += $commissionAmount;
        $affiliate->save();

        AffiliateTransaction::create([
            'affiliate_id' => $affiliate->id,
            'type' => 'pending',
            'referral_user_id' => $user->id, // Link to the user who made the payment
            'image' => null, // No image for pending transactions
            'amount' => $commissionAmount,
            'note' => 'Commission for referral user_id: ' . $user->id . ' user_username: (' . $user->username . ') for package: ' . $membership->package->title,
        ]);

        $this->command->info("✅ Simulated commission of $commissionAmount SAR logged for affiliate ID: {$affiliate->id}");
    }
}
