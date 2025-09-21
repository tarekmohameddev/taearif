<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\BasicSetting;
use App\Models\BasicExtended;
use App\Models\Package;
use App\Services\WhatsAppService;
use App\Services\EmailService;
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
            $emailService = new EmailService();
            $be = BasicExtended::first();
            $sentCount = 0;
            $failedCount = 0;

            foreach ($expiringMemberships as $membership) {
                if (!$membership->user) {
                    $this->warn("Skipping user {$membership->user_id}: User not found");
                    continue;
                }

                $packageName = $membership->package ? $membership->package->title : 'Unknown Package';
                $expiryDate = Carbon::parse($membership->expire_date)->format('Y-m-d');
                $user = $membership->user;

                // Send WhatsApp reminder
                if (!empty($user->phone)) {
                    try {
                        $whatsappService->sendSubscriptionExpirationMessage(
                            $user->phone,
                            $bs->subscription_expiration_text,
                            $user->first_name,
                            $packageName,
                            $expiryDate
                        );

                        $this->info("Sent WhatsApp reminder to: {$user->first_name} ({$user->phone})");
                        
                    } catch (\Exception $e) {
                        $this->error("Failed to send WhatsApp reminder to user {$user->id}: " . $e->getMessage());
                        
                        Log::error('WhatsApp subscription expiration reminder failed', [
                            'user_id' => $user->id,
                            'phone' => $user->phone,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    $this->warn("Skipping WhatsApp for user {$user->id}: No phone number");
                }

                // Send email reminder
                if (!empty($user->email) && $be && $be->subscription_expiration_email_enabled) {
                    try {
                        $templateName = $be->subscription_expiration_template ?? null;
                        
                        $emailService->sendSubscriptionExpirationEmail(
                            $user->email,
                            $user->first_name,
                            $packageName,
                            $expiryDate,
                            'ar', // Default language
                            $templateName
                        );

                        $this->info("Sent email reminder to: {$user->first_name} ({$user->email})");
                        $sentCount++;
                        
                    } catch (\Exception $e) {
                        $failedCount++;
                        $this->error("Failed to send email reminder to user {$user->id}: " . $e->getMessage());
                        
                        Log::error('Email subscription expiration reminder failed', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage()
                        ]);
                    }
                } else {
                    if (empty($user->email)) {
                        $this->warn("Skipping email for user {$user->id}: No email address");
                    } elseif (!$be || !$be->subscription_expiration_email_enabled) {
                        $this->info("Skipping email for user {$user->id}: Email notifications disabled");
                    }
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
