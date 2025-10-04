<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunAllScheduledTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:run-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all scheduled commands immediately (for testing)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Running all scheduled commands...');
        $this->newLine();

        $commands = [
            ['command' => 'expire:user', 'args' => []],
            ['command' => 'app:expire-trials', 'args' => []],
            ['command' => 'reminders:process', 'args' => []],
            ['command' => 'health:check', 'args' => ['--auto' => true]],
            ['command' => 'subscription:send-expiration-reminders', 'args' => []],
        ];

        foreach ($commands as $cmd) {
            $commandName = $cmd['command'];
            $args = $cmd['args'];
            
            $displayArgs = '';
            foreach ($args as $key => $value) {
                if (is_string($key) && strpos($key, '--') === 0) {
                    $displayArgs .= " {$key}";
                }
            }
            
            $this->info("Running: php artisan {$commandName}{$displayArgs}");
            $this->call($commandName, $args);
            $this->newLine();
        }

        $this->info('All scheduled commands completed!');
        
        return self::SUCCESS;
    }
}

