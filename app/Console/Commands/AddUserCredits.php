<?php

namespace App\Console\Commands;

use App\Models\Api\markting\UserCredit;
use App\Models\User;
use Illuminate\Console\Command;

class AddUserCredits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'credits:add
                            {user_id : The user ID to add credits for}
                            {amount : Number of credits to add (positive integer)}
                            {--description= : Optional description for the transaction}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add credits to a user by user ID and amount';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $amount = (int) $this->argument('amount');
        $description = $this->option('description') ?? 'Credits added via artisan';

        if ($userId <= 0) {
            $this->error('user_id must be a positive integer.');
            return self::FAILURE;
        }

        if ($amount <= 0) {
            $this->error('amount must be a positive integer.');
            return self::FAILURE;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found.");
            return self::FAILURE;
        }

        $userCredit = UserCredit::getOrCreateForUser($userId);
        $userCredit->addCredits($amount, null, $description);

        $userCredit->refresh();
        $this->info("Added {$amount} credits to user ID {$userId}.");
        $this->info("Total credits: {$userCredit->total_credits}, Used: {$userCredit->used_credits}, Available: {$userCredit->available_credits}.");

        return self::SUCCESS;
    }
}
