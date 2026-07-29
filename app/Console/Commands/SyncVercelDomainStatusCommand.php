<?php

namespace App\Console\Commands;

use App\Models\Api\ApiDomainSetting;
use App\Services\Vercel\DomainStatusSyncService;
use App\Services\Vercel\VercelDomainClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncVercelDomainStatusCommand extends Command
{
    protected $signature = 'domains:sync-vercel-status {--chunk=50 : Number of domains per chunk}';

    protected $description = 'Sync custom domain status/ssl from Vercel, nameservers, and expiry';

    public function handle(DomainStatusSyncService $sync, VercelDomainClient $vercel): int
    {
        $autoAttach = (bool) config('services.vercel.auto_attach_custom_domain', true);
        $checkNameservers = (bool) config('services.vercel.check_nameservers', true);

        if (! $autoAttach && ! $checkNameservers) {
            $this->warn('Domain verification checks are disabled; skipping domains:sync-vercel-status.');
            Log::warning('domains:sync-vercel-status skipped: both auto_attach and check_nameservers disabled');

            return self::SUCCESS;
        }

        if ($autoAttach && ! $vercel->isConfigured()) {
            $this->warn('Vercel is not configured; skipping domains:sync-vercel-status.');
            Log::warning('domains:sync-vercel-status skipped: Vercel not configured');

            return self::SUCCESS;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $checked = 0;
        $activated = 0;
        $failed = 0;
        $unchanged = 0;
        $errors = 0;

        ApiDomainSetting::query()
            ->orderBy('id')
            ->chunkById($chunk, function ($domains) use ($sync, &$checked, &$activated, &$failed, &$unchanged, &$errors) {
                foreach ($domains as $domain) {
                    $checked++;
                    try {
                        $attemptVerify = $domain->status === 'pending';
                        $result = $sync->sync($domain, $attemptVerify);

                        if (! $result['changed']) {
                            $unchanged++;
                        } elseif ($result['new_status'] === 'active') {
                            $activated++;
                        } elseif ($result['new_status'] === 'failed') {
                            $failed++;
                        } else {
                            $unchanged++;
                        }
                    } catch (Throwable $e) {
                        $errors++;
                        Log::error('domains:sync-vercel-status failed for domain', [
                            'domain_id' => $domain->id,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    usleep(50_000);
                }
            });

        $summary = compact('checked', 'activated', 'failed', 'unchanged', 'errors');
        $this->info('Domain sync complete: ' . json_encode($summary));
        Log::info('domains:sync-vercel-status complete', $summary);

        return self::SUCCESS;
    }
}
