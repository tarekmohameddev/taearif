<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Rms\ReminderGeneratorService;

/**
 * Class ProcessRmsReminders
 *
 * This command processes RMS reminders, generating payment and contract reminders.
 */

class ProcessRmsReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate RMS payment and contract reminders';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Processing RMS reminders...');
        app(ReminderGeneratorService::class)->run();
        $this->info('RMS reminders processed successfully.');
        return Command::SUCCESS;
    }
}
