<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CustomRouteListCommand::class,

        Commands\ExpiredUser::class,
        \App\Console\Commands\ProcessRmsReminders::class,
        \App\Console\Commands\SeedGlobalPermissions::class,
        \App\Console\Commands\HealthCheck::class,
        \App\Console\Commands\SendSubscriptionExpirationReminders::class,
        \App\Console\Commands\TestWhatsAppIntegrations::class,
        \App\Console\Commands\TestWhatsAppMessage::class,
        \App\Console\Commands\RunAllScheduledTasks::class,

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('expire:user')->daily();
        $schedule->command('app:expire-trials')->daily();
        $schedule->command('reminders:process')->dailyAt('04:00')->timezone('Asia/Riyadh');
        $schedule->command('health:check --auto')->dailyAt('03:55')->timezone('Asia/Riyadh');
        
        // Schedule subscription expiration reminders
        // This will run daily at the configured time (default 09:00)
        $schedule->command('subscription:send-expiration-reminders')
            ->daily()
            ->when(function () {
                $bs = \App\Models\BasicSetting::first();
                return $bs && $bs->subscription_expiration_enabled;
            })
            ->timezone('Asia/Riyadh');

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
