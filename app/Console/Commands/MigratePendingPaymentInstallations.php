<?php

namespace App\Console\Commands;

use App\Enums\InstallStatus;
use App\Models\Api\ApiInstallation;
use App\Models\Api\AppPaymentTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MigratePendingPaymentInstallations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-pending-installations 
                            {--dry-run : Show what would be migrated without making changes}
                            {--force : Force migration without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate pending_payment installations to installed status (safe migration)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Checking for pending_payment installations...');
        $this->newLine();

        // Find all installations with pending_payment status
        $pendingInstallations = ApiInstallation::where('status', InstallStatus::PendingPayment)
            ->with(['app', 'user', 'paymentTransactions'])
            ->get();

        if ($pendingInstallations->isEmpty()) {
            $this->info('✓ No pending_payment installations found. Database is clean!');
            return self::SUCCESS;
        }

        $this->warn("Found {$pendingInstallations->count()} installation(s) with pending_payment status:");
        $this->newLine();

        // Display summary table
        $tableData = [];
        foreach ($pendingInstallations as $install) {
            $hasPendingTransaction = $install->paymentTransactions()
                ->where('status', 'pending')
                ->exists();
            
            $hasCompletedTransaction = $install->hasCompletedPayment();

            $tableData[] = [
                'ID' => $install->id,
                'User ID' => $install->user_id,
                'App' => $install->app->name ?? 'N/A',
                'Has Pending Payment' => $hasPendingTransaction ? 'Yes' : 'No',
                'Has Completed Payment' => $hasCompletedTransaction ? 'Yes' : 'No',
                'Created At' => $install->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table(
            ['ID', 'User ID', 'App', 'Has Pending Payment', 'Has Completed Payment', 'Created At'],
            $tableData
        );
        $this->newLine();

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->info('Run without --dry-run to perform the migration');
            return self::SUCCESS;
        }

        // Ask for confirmation
        if (!$force) {
            if (!$this->confirm('Do you want to migrate these installations to "installed" status?', true)) {
                $this->info('Migration cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Starting migration...');
        $this->newLine();

        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        DB::beginTransaction();
        try {
            foreach ($pendingInstallations as $install) {
                try {
                    // Check if there's a completed payment transaction
                    $hasCompletedPayment = $install->hasCompletedPayment();
                    
                    // Migrate to installed status
                    $install->update([
                        'status' => InstallStatus::Installed,
                        'installed' => true,
                        'installed_at' => $install->installed_at ?? now(),
                    ]);

                    $migratedCount++;
                    
                    $status = $hasCompletedPayment ? '✓ (has completed payment)' : '⚠ (no completed payment)';
                    $this->line("  {$status} Installation ID {$install->id} migrated to 'installed'");

                    Log::info('Migrated pending_payment installation to installed', [
                        'installation_id' => $install->id,
                        'user_id' => $install->user_id,
                        'app_id' => $install->app_id,
                        'has_completed_payment' => $hasCompletedPayment,
                    ]);

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  ✗ Failed to migrate Installation ID {$install->id}: {$e->getMessage()}");
                    
                    Log::error('Failed to migrate pending_payment installation', [
                        'installation_id' => $install->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('Migration completed!');
            $this->info("  ✓ Migrated: {$migratedCount}");
            if ($errorCount > 0) {
                $this->warn("  ✗ Errors: {$errorCount}");
            }

            // Verify migration
            $remaining = ApiInstallation::where('status', InstallStatus::PendingPayment)->count();
            if ($remaining === 0) {
                $this->info('  ✓ All pending_payment installations have been migrated!');
            } else {
                $this->warn("  ⚠ Warning: {$remaining} installations still have pending_payment status");
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Migration failed and was rolled back: ' . $e->getMessage());
            Log::error('Migration of pending_payment installations failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
