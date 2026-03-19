<?php

namespace App\Console\Commands;

use App\Models\PasswordResetLog;
use App\Models\User;
use Illuminate\Console\Command;

class ClearPasswordResetAttemptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'password-reset:clear-attempts
                            {identifier : User email or numeric user id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete password_reset_logs for a user (clears API forgot-password attempt limits)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $identifier = trim((string) $this->argument('identifier'));

        if ($identifier === '') {
            $this->error('identifier must not be empty.');
            return self::FAILURE;
        }

        $user = ctype_digit($identifier)
            ? User::query()->find((int) $identifier)
            : User::query()->where('email', $identifier)->first();

        if ($user === null) {
            $this->error('User not found.');
            return self::FAILURE;
        }

        $deleted = PasswordResetLog::query()->where('user_id', $user->id)->delete();

        $this->info("Removed {$deleted} row(s) from password_reset_logs for user_id={$user->id} ({$user->email}).");

        return self::SUCCESS;
    }
}
