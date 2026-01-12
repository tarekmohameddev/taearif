<?php

namespace App\Console\Commands;

use App\Enums\InstallStatus;
use App\Models\Api\ApiInstallation;
use Illuminate\Console\Command;

class CheckPendingPaymentInstallations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-pending-installations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for installations with pending_payment status in the database';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking database for pending_payment installations...');
        $this->newLine();

        $count = ApiInstallation::where('status', InstallStatus::PendingPayment)->count();

        if ($count === 0) {
            $this->info('✓ No installations with pending_payment status found.');
            $this->info('  Database is clean and ready for PendingPayment enum removal.');
            return self::SUCCESS;
        }

        $this->warn("⚠ Found {$count} installation(s) with pending_payment status:");
        $this->newLine();

        $installations = ApiInstallation::where('status', InstallStatus::PendingPayment)
            ->with(['app', 'user', 'paymentTransactions'])
            ->get();

        $tableData = [];
        foreach ($installations as $install) {
            $hasPendingTransaction = $install->paymentTransactions()
                ->where('status', 'pending')
                ->exists();
            
            $hasCompletedTransaction = $install->hasCompletedPayment();

            $tableData[] = [
                'ID' => $install->id,
                'User ID' => $install->user_id,
                'App Name' => $install->app->name ?? 'N/A',
                'App ID' => $install->app_id,
                'Pending Payment' => $hasPendingTransaction ? 'Yes' : 'No',
                'Completed Payment' => $hasCompletedTransaction ? 'Yes' : 'No',
                'Created' => $install->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table(
            ['ID', 'User ID', 'App Name', 'App ID', 'Pending Payment', 'Completed Payment', 'Created'],
            $tableData
        );

        $this->newLine();
        $this->info('To migrate these installations, run:');
        $this->line('  php artisan app:migrate-pending-installations --dry-run  (preview)');
        $this->line('  php artisan app:migrate-pending-installations            (migrate)');

        return self::SUCCESS;
    }
}
