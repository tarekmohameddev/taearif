<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Membership;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\AffiliateTransaction;
use App\Models\Api\ApiAffiliateUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SimulateAffiliatePaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // === Test Inputs ===
        $userId      = 867;   // The paying user
        $referred_by = 829;   // The affiliate (owner of ApiAffiliateUser.user_id)

        // Pick ONE scenario to test:
        // 'expired'  => to_date_value = yesterday  -> expect SKIP
        // 'today'    => to_date_value = today      -> expect COMMISSION
        // 'future'   => to_date_value = tomorrow   -> expect COMMISSION
        // 'no_expiry'=> to_date_value = NULL       -> expect COMMISSION
        // $scenario = 'today';
        $scenario = 'expired';

        // Optional: force app timezone for the test run if needed
        // config(['app.timezone' => 'Africa/Cairo']);

        $user = User::find($userId);
        if (!$user) {
            $this->command->error("User with ID $userId not found.");
            return;
        }

        // Ensure an ApiAffiliateUser exists for $referred_by and set its to_date_value based on scenario
        $toDate = match ($scenario) {
            'expired'   => now()->subDay()->toDateString(),   // yesterday
            'today'     => now()->toDateString(),
            'future'    => now()->addDay()->toDateString(),
            'no_expiry' => null,
            default     => now()->toDateString(),
        };

        $affiliate = ApiAffiliateUser::firstOrCreate(
            ['user_id' => $referred_by],
            [
                'fullname'              => 'Test Affiliate',
                'bank_name'             => 'N/A',
                'bank_account_number'   => 'N/A',
                'iban'                  => 'N/A',
                'commission_percentage' => 0.15,
                'start_date_value'      => now()->toDateString(), // not used in the check, but fine to set
                'to_date_value'         => $toDate,
                'pending_amount'        => 0,
                'request_status'        => 'approved',
                'image'                 => null,
            ]
        );

        // If it already exists, update to match the chosen scenario
        $affiliate->to_date_value = $toDate;
        // Make sure commission rate and pending_amount are set for clean tests
        $affiliate->commission_percentage = $affiliate->commission_percentage ?? 0.15;
        $affiliate->pending_amount = $affiliate->pending_amount ?? 0;
        $affiliate->save();

        // 1) Update referral link on the user
        $user->subscription_amount = 299;
        $user->subscribed = true;
        $user->referred_by = $referred_by;
        $user->save();

        // 2) Create a simulated membership (same as your code)
        try {
            $membership = Membership::create([
                'user_id'            => $user->id,
                'package_id'         => 16,
                'price'              => 299,
                'package_price'      => 299,
                'currency'           => 'SAR',
                'currency_symbol'    => '﷼',
                'transaction_id'     => Str::upper(Str::random(10)),
                'payment_method'     => 'Manual',
                'is_trial'           => false,
                'trial_days'         => 0,
                'receipt'            => null,
                'transaction_details'=> null,
                'settings'           => null,
                'discount'           => 0,
                'coupon_code'        => null,
                'modified'           => 0,
                'conversation_id'    => null,
                'status'             => 1,
                'start_date'         => now(),
                'expire_date'        => now()->addMonth(),
            ]);
            $this->command->info("Membership created with ID: {$membership->id}");
        } catch (\Exception $e) {
            $this->command->error("Failed to create membership: " . $e->getMessage());
            return;
        }

        // 3) Try to log affiliate commission with "valid until today" logic.
        //    We’ll also print the decision so you can see PASS/FAIL clearly.
        DB::transaction(function () use ($user, $membership, $affiliate, $scenario) {

            // Reload/lock latest affiliate row to simulate real payment behavior
            $locked = ApiAffiliateUser::where('id', $affiliate->id)->lockForUpdate()->first();

            $validUntilToday = is_null($locked->to_date_value) || $locked->to_date_value->copy()->endOfDay()->gte(now()); // inclusive "until today"

            $this->command->info("Scenario: {$scenario}");
            $this->command->info("Affiliate to_date_value: " . ($locked->to_date_value?->toDateString() ?? 'NULL'));
            $this->command->info("Now: " . now()->toDateTimeString());
            $this->command->info("Valid until today? " . ($validUntilToday ? 'YES' : 'NO'));

            if (!$validUntilToday) {
                // SKIP path — no commission created
                $this->command->warn('Commission skipped: affiliate expired.');
                return;
            }

            // COMMISSION path
            $commissionRate   = $locked->commission_percentage ?? 0.15;
            $commissionAmount = round($user->subscription_amount * $commissionRate, 2);

            $locked->pending_amount = ($locked->pending_amount ?? 0) + $commissionAmount;
            $locked->save();

            AffiliateTransaction::create([
                'affiliate_id'     => $locked->id,
                'type'             => 'pending',
                'referral_user_id' => $user->id,
                'image'            => null,
                'amount'           => $commissionAmount,
                'note'             => 'Commission for referral user_id: ' . $user->id
                                       . ' user_username: (' . $user->username . ')'
                                       . ' for package: ' . ($membership->package->title ?? 'N/A'),
            ]);

            $this->command->info("✅ Commission of {$commissionAmount} SAR logged for affiliate ID: {$locked->id}");
        });
    }

}
