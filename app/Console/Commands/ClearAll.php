<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:all {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all Laravel caches (config, cache, route, view, compiled)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to clear all caches...');

        // Clear configuration cache
        $this->info('Clearing configuration cache...');
        Artisan::call('config:clear');
        $this->line('Configuration cache cleared');

        // Clear application cache
        $this->info('Clearing application cache...');
        Artisan::call('cache:clear');
        $this->line('Application cache cleared');

        // Clear route cache
        $this->info('Clearing route cache...');
        Artisan::call('route:clear');
        $this->line('Route cache cleared');

        // Clear view cache
        $this->info('Clearing view cache...');
        Artisan::call('view:clear');
        $this->line('View cache cleared');

        // Clear compiled classes
        $this->info('Clearing compiled classes...');
        Artisan::call('clear-compiled');
        $this->line('Compiled classes cleared');

        // Optional: Clear event cache (Laravel 6+)
        if (method_exists(\Illuminate\Support\Facades\Artisan::class, 'call')) {
            try {
                Artisan::call('event:clear');
                $this->line('Event cache cleared');
            } catch (\Exception $e) {
                // Event cache clearing not available in this Laravel version
            }
        }

        $this->info('All caches cleared successfully!');

        return Command::SUCCESS;
    }
}
