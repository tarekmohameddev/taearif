<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use App\Models\BasicExtended;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SendWelcomeMessageJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $message;
    
    /**
     * The number of seconds after which the job's unique lock will be released.
     *
     * @var int
     */
    public $uniqueFor = 3600; // 1 hour

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, string $message)
    {
        $this->user = $user;
        $this->message = $message;
    }

    /**
     * The unique ID of the job.
     *
     * @return string
     */
    public function uniqueId()
    {
        return 'welcome-message-' . $this->user->id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Check if welcome message was already sent to prevent duplicates
        $cacheKey = 'welcome_message_sent_' . $this->user->id;
        
        if (Cache::has($cacheKey)) {
            Log::info('Welcome message already sent, skipping duplicate', [
                'user_id' => $this->user->id,
                'phone' => $this->user->phone,
                'email' => $this->user->email
            ]);
            return;
        }

        try {
            $messageSent = false;

            // Send WhatsApp welcome message
            try {
                $whatsappService = new WhatsAppService();
                $bs = \App\Models\BasicSetting::first();
                
                // Only send WhatsApp if enabled and phone is available
                if ($bs && $bs->welcome_message_enabled && !empty($bs->welcome_message_text) && !empty($this->user->phone)) {
                    // Variables will be replaced by the service with company name logic
                    $message = str_replace('{email}', $this->user->email ?? 'N/A', $this->message);
                    
                    $whatsappService->sendWelcomeMessage($this->user->phone, $message, $this->user->first_name, $this->user->email, $this->user->id);
                    
                    $messageSent = true;
                    
                    Log::info('WhatsApp welcome message sent successfully', [
                        'user_id' => $this->user->id,
                        'phone' => $this->user->phone
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('WhatsApp welcome message failed', [
                    'user_id' => $this->user->id,
                    'phone' => $this->user->phone,
                    'error' => $e->getMessage()
                ]);
                // Don't re-throw here, continue with email
            }

            // Send email welcome message
            try {
                $emailService = new EmailService();
                $be = BasicExtended::first();
                
                // Check if email notifications are enabled
                if ($be && $be->welcome_message_email_enabled && !empty($this->user->email)) {
                    // Get template name from settings
                    $templateName = $be->welcome_message_template ?? null;
                    
                    $emailService->sendWelcomeEmail(
                        $this->user->email,
                        $this->user->first_name ?? 'User',
                        'ar', // Default language
                        $templateName,
                        $this->user->id
                    );
                    
                    $messageSent = true;
                    
                    Log::info('Email welcome message sent successfully', [
                        'user_id' => $this->user->id,
                        'email' => $this->user->email
                    ]);
                } else {
                    Log::info('Email welcome message skipped', [
                        'user_id' => $this->user->id,
                        'email' => $this->user->email,
                        'enabled' => $be ? $be->welcome_message_email_enabled : false
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Email welcome message failed', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'error' => $e->getMessage()
                ]);
                // Don't re-throw here, job should complete even if email fails
            }
            
            // Mark welcome message as sent to prevent duplicates
            // Cache for 24 hours to prevent duplicate sends
            if ($messageSent) {
                Cache::put($cacheKey, true, now()->addHours(24));
                
                Log::info('Welcome message sent flag cached', [
                    'user_id' => $this->user->id,
                    'cache_key' => $cacheKey
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Welcome message job failed completely', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

}
