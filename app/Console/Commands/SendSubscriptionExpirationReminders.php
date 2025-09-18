<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\BasicSetting;
use App\Models\Package;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionExpirationReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:send-expiration-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send WhatsApp reminders for expiring subscriptions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $bs = BasicSetting::first();
            
            if (!$bs || !$bs->subscription_expiration_enabled || empty($bs->subscription_expiration_text)) {
                $this->info('Subscription expiration reminders are disabled or not configured.');
                return self::SUCCESS;
            }

            $daysBefore = $bs->subscription_expiration_days_before ?? 3;
            $targetDate = Carbon::now()->addDays($daysBefore)->toDateString();
            
            $this->info("Checking for subscriptions expiring on: {$targetDate}");

            // Get memberships expiring in the specified number of days
            $expiringMemberships = Membership::where('status', 1)
                ->whereDate('expire_date', $targetDate)
                ->with(['user', 'package'])
                ->get();

            if ($expiringMemberships->isEmpty()) {
                $this->info('No subscriptions found expiring on the target date.');
                return self::SUCCESS;
            }

            $whatsappService = new WhatsAppService();
            $sentCount = 0;
            $failedCount = 0;

            foreach ($expiringMemberships as $membership) {
                if (!$membership->user || empty($membership->user->phone)) {
                    $this->warn("Skipping user {$membership->user_id}: No phone number");
                    continue;
                }

                try {
                    $packageName = $membership->package ? $membership->package->title : 'Unknown Package';
                    $expiryDate = Carbon::parse($membership->expire_date)->format('Y-m-d');
                    
                    $whatsappService->sendSubscriptionExpirationMessage(
                        $membership->user->phone,
                        $bs->subscription_expiration_text,
                        $membership->user->first_name,
                        $packageName,
                        $expiryDate
                    );

                    $sentCount++;
                    $this->info("Sent reminder to: {$membership->user->first_name} ({$membership->user->phone})");
                    
                } catch (\Exception $e) {
                    $failedCount++;
                    $this->error("Failed to send reminder to user {$membership->user_id}: " . $e->getMessage());
                    
                    Log::error('Subscription expiration reminder failed', [
                        'user_id' => $membership->user_id,
                        'phone' => $membership->user->phone,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->info("Reminder sending completed. Sent: {$sentCount}, Failed: {$failedCount}");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Command failed: ' . $e->getMessage());
            Log::error('Subscription expiration reminder command failed', [
                'error' => $e->getMessage()
            ]);
            return self::FAILURE;
        }
    }
}
