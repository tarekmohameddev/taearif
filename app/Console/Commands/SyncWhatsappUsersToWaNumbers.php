<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Console\Command;

final class SyncWhatsappUsersToWaNumbers extends Command
{
    protected $signature = 'whatsapp:sync-wa-numbers {--dry-run} {--user-id=}';

    protected $description = 'Backfill WaNumber rows from active whatsapp_users records so AI bot can reply from the correct number.';

    public function handle(): int
    {
        $query = WhatsappUser::where('status', 'active');

        if ($this->option('user-id')) {
            $query->where('user_id', (int) $this->option('user-id'));
        }

        $records = $query->get();

        $created = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $wu) {
            try {
                $phoneId = (string) ($wu->phone_id ?? '');

                if ($phoneId === '') {
                    $failed++;
                    $bar->advance();
                    continue;
                }

                $isMeta = ! empty($wu->token) || ! empty($wu->access_token);

                if ($isMeta) {
                    $exists = WaNumber::where('user_id', $wu->user_id)
                        ->where('phone_number_id', $phoneId)
                        ->exists();
                } else {
                    $exists = WaNumber::where('user_id', $wu->user_id)
                        ->where('provider_account_id', $phoneId)
                        ->exists();
                }

                if ($exists) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $normalized = $this->normalizePhone((string) ($wu->number ?? ''));

                // Count every row that would be created so dry-run output is accurate.
                // The [DRY-RUN] prefix already clarifies that nothing was written.
                $created++;

                if (! $this->option('dry-run')) {
                    if ($isMeta) {
                        WaNumber::create([
                            'user_id'         => $wu->user_id,
                            'provider'        => 'meta',
                            'phone_number'    => $normalized,
                            'phone_number_id' => $phoneId,
                            'name'            => $wu->name ?? null,
                            'status'          => 'active',
                            'meta'            => [
                                'access_token'    => $wu->access_token ?? $wu->token,
                                'phone_number_id' => $phoneId,
                            ],
                        ]);
                    } else {
                        WaNumber::create([
                            'user_id'              => $wu->user_id,
                            'provider'             => 'evolution',
                            'phone_number'         => $normalized,
                            'provider_account_id'  => $phoneId,
                            'name'                 => $wu->name ?? null,
                            'status'               => 'active',
                            'meta'                 => ['instance' => $phoneId],
                        ]);
                    }
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
        $this->info("{$prefix}Created: {$created} | Skipped: {$skipped} | Failed: {$failed}");

        return self::SUCCESS;
    }

    private function normalizePhone(string $number): string
    {
        $cleaned = preg_replace('/[\s\-]/', '', $number) ?? $number;

        if (preg_match('/^\d+$/', $cleaned) && ! str_starts_with($cleaned, '0')) {
            return '+' . $cleaned;
        }

        return $cleaned;
    }
}
