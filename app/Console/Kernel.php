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
        \App\Console\Commands\SendCrmAppointmentReminders::class,
        \App\Console\Commands\BackfillInvoiceUuids::class,
        \App\Console\Commands\CleanupOldPageviewsCommand::class,
        \App\Console\Commands\ProcessScheduledSmsCampaigns::class,
        \App\Console\Commands\ProcessScheduledEmailCampaigns::class,
        \App\Console\Commands\BackfillRbacNewPermissions::class,
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
        $schedule->command('app:verify-pending-payments')->everyThirtyMinutes();
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

        // Schedule CRM appointment reminders (send 2 hours before appointment)
        $schedule->command('crm:send-appointment-reminders')
            ->hourly()
            ->timezone('Asia/Riyadh');

        // Sync GA4 analytics data every 6 hours (02:00, 08:00, 14:00, 20:00)
        $schedule->command('analytics:sync')
            ->everySixHours()
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping()
            ->runInBackground();

        // Pre-warm property filter options cache (runs every 50 minutes to refresh before cache expiration)
        // Cache TTL is 1 hour, so this runs every 50 minutes to keep cache fresh
        $schedule->command('properties:prewarm-filter-cache')
            ->cron('*/50 * * * *') // Every 50 minutes
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping()
            ->runInBackground();

        // Clean up old pageview analytics records (runs weekly)
        $schedule->command('analytics:cleanup-old-pageviews')
            ->weekly()
            ->timezone('Asia/Riyadh')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('sms:process-scheduled-campaigns')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('email:process-scheduled-campaigns')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('communication:process-retries')
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('communication:reconcile-delivery')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('communication:prune-reliability-data')
            ->daily()
            ->withoutOverlapping()
            ->onOneServer();
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
