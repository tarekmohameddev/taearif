<?php

namespace App\Listeners;

use App\Models\User;
use App\Models\Api\markting\UserCredit;
use App\Models\Api\markting\CreditTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GiveWelcomeCredits implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        // Get the user from the event
        $user = $event->user ?? $event;
        
        // Only give credits to tenant users (not employees)
        if ($user instanceof User && $user->isTenant()) {
            $this->giveWelcomeCredits($user);
        }
    }

    /**
     * Give 3000 welcome credits to new user
     *
     * @param User $user
     * @return void
     */
    private function giveWelcomeCredits(User $user)
    {
        try {
            // Get or create user credit record
            $userCredit = UserCredit::getOrCreateForUser($user->id);
            
            // Add 3000 welcome credits
            $userCredit->addCredits(
                3000, 
                null, 
                'Welcome credits - New user registration'
            );
            
            // Create a specific welcome transaction record
            CreditTransaction::create([
                'user_id' => $user->id,
                'credit_package_id' => null,
                'transaction_type' => 'welcome_bonus',
                'credits_amount' => 3000,
                'amount_paid' => 0, // Free credits
                'currency' => 'SAR',
                'payment_method' => 'system',
                'status' => 'completed',
                'reference_number' => CreditTransaction::generateReferenceNumber(),
                'description' => 'Welcome bonus - 3000 free credits for new registration',
                'metadata' => [
                    'type' => 'welcome_bonus',
                    'granted_at' => now()->toISOString(),
                    'registration_date' => $user->created_at->toISOString(),
                ],
            ]);
            
            Log::info("Welcome credits granted to user {$user->id}: 3000 credits");
            
        } catch (\Exception $e) {
            Log::error("Failed to give welcome credits to user {$user->id}: " . $e->getMessage());
        }
    }
}
