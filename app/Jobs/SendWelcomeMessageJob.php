<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\WhatsAppService;
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
            $whatsappService = new WhatsAppService();
            
            // Replace variables in message
            $message = str_replace('{name}', $this->user->first_name ?? 'User', $this->message);
            $message = str_replace('{email}', $this->user->email ?? 'N/A', $message);
            
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
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }
}
