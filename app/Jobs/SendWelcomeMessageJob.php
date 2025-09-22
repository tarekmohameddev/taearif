<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use App\Models\BasicExtended;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWelcomeMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $message;

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
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Send WhatsApp welcome message
            try {
                $whatsappService = new WhatsAppService();
                
                // Replace variables in message
                $message = str_replace('{name}', $this->user->first_name ?? 'User', $this->message);
                $message = str_replace('{email}', $this->user->email ?? 'N/A', $message);
                
                // Clean message for WhatsApp (remove newlines and excessive spaces)
                $message = $this->cleanMessageForWhatsApp($message);
                
                $whatsappService->sendWelcomeMessage($this->user->phone, $message, $this->user->first_name);
                
                Log::info('WhatsApp welcome message sent successfully', [
                    'user_id' => $this->user->id,
                    'phone' => $this->user->phone
                ]);
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
                        $templateName
                    );
                    
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
            
        } catch (\Exception $e) {
            Log::error('Welcome message job failed completely', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Clean message content for WhatsApp template parameters
     * WhatsApp doesn't allow newlines, tabs, or more than 4 consecutive spaces
     */
    private function cleanMessageForWhatsApp($message)
    {
        // Replace newlines and tabs with spaces
        $message = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $message);
        
        // Replace multiple consecutive spaces with single space
        $message = preg_replace('/\s+/', ' ', $message);
        
        // Trim leading and trailing spaces
        $message = trim($message);
        
        return $message;
    }
}
