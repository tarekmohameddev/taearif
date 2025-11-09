<?php

namespace App\Console\Commands;

use App\Domain\Billing\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BackfillInvoiceUuids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:backfill-uuids {--dry-run : Show how many invoices would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign UUIDs to invoices (memberships) that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $table = (new Invoice())->getTable();
        if (!Schema::hasColumn($table, 'uuid')) {
            $this->warn("The {$table} table does not have a uuid column. Nothing to backfill.");
            return self::SUCCESS;
        }

        $query = Invoice::query()->whereNull('uuid');
        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('All invoices already have UUIDs. Nothing to do.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("Dry run: {$total} invoice(s) would receive new UUIDs.");
            return self::SUCCESS;
        }

        $updated = 0;
        $this->info("Updating {$total} invoice(s) without UUIDs...");

        DB::transaction(function () use ($query, &$updated) {
            $query->select(['id'])
                ->orderBy('id')
                ->chunkById(500, function ($invoices) use (&$updated) {
                    foreach ($invoices as $invoice) {
                        $uuid = (string) Str::uuid();

                        Invoice::where('id', $invoice->id)->update([
                            'uuid' => $uuid,
                            'updated_at' => now(),
                        ]);

                        $updated++;
                    }
                });
        });

        $this->info("Finished backfilling UUIDs for {$updated} invoice(s).");

        return self::SUCCESS;
    }
}

