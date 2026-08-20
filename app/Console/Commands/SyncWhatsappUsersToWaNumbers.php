<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communication\WhatsApp\Services\SyncWhatsappUserToWaNumberService;
use App\Models\WhatsappUser;
use Illuminate\Console\Command;

final class SyncWhatsappUsersToWaNumbers extends Command
{
    protected $signature = 'whatsapp:sync-wa-numbers {--dry-run} {--user-id=}';

    protected $description = 'Backfill WaNumber rows from whatsapp_users records so AI bot / v1 WhatsApp APIs use the correct number.';

    public function __construct(
        private readonly SyncWhatsappUserToWaNumberService $syncService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = WhatsappUser::query()->whereNotNull('phone_id')->where('phone_id', '!=', '');

        if ($this->option('user-id')) {
            $query->where('user_id', (int) $this->option('user-id'));
        }

        $records = $query->get();

        $synced = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $wu) {
            try {
                if ($this->option('dry-run')) {
                    $phoneId = trim((string) ($wu->phone_id ?? ''));
                    if ($phoneId === '') {
                        $skipped++;
                    } else {
                        $synced++;
                    }
                    $bar->advance();
                    continue;
                }

                $result = $this->syncService->sync($wu);
                if ($result === null) {
                    $skipped++;
                } else {
                    $synced++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed for whatsapp_user #{$wu->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $prefix = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}Synced: {$synced} | Skipped: {$skipped} | Failed: {$failed}");

        return self::SUCCESS;
    }
}
