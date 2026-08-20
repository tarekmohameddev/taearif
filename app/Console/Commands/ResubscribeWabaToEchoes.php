<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\WhatsappUser;
use App\Services\MetaGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Re-subscribe all active WABAs to the message_echoes webhook field.
 *
 * WABAs connected before the July 2026 subscription change were registered with
 * the invalid field name `message_echo` (no trailing 's'). Meta silently ignores
 * unknown fields, so those numbers never receive outbound echoes. This command
 * re-runs the subscription call for every active number, replacing the old field
 * list with the correct one that now includes `message_echoes` and
 * `smb_message_echoes`.
 *
 * Usage:
 *   php artisan wa:resubscribe-echoes              # live run
 *   php artisan wa:resubscribe-echoes --dry-run    # show what would happen
 *   php artisan wa:resubscribe-echoes --verify     # read back subscription and print
 */
final class ResubscribeWabaToEchoes extends Command
{
    protected $signature = 'wa:resubscribe-echoes
                            {--dry-run : Print what would be done without making any API calls}
                            {--verify  : After subscribing, read back the subscribed fields from the Graph API and print them}
                            {--waba=   : Restrict to a single WABA ID}';

    protected $description = 'Re-subscribe all active WABAs to the message_echoes and smb_message_echoes webhook fields';

    public function __construct(
        private readonly MetaGraphService $graphService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $verify = (bool) $this->option('verify');
        $targetWaba = $this->option('waba') ? (string) $this->option('waba') : null;

        if ($dryRun) {
            $this->warn('[DRY RUN] No API calls will be made.');
        }

        $query = WhatsappUser::query()
            ->where('status', 'active')
            ->whereNotNull('waba_id')
            ->whereNotNull('access_token')
            ->where('access_token', '!=', '');

        if ($targetWaba !== null) {
            $query->where('waba_id', $targetWaba);
        }

        $numbers = $query->get(['id', 'user_id', 'waba_id', 'access_token', 'number']);

        if ($numbers->isEmpty()) {
            $this->info('No active WhatsApp numbers found.');
            return self::SUCCESS;
        }

        $this->info("Found {$numbers->count()} active WhatsApp number(s). Re-subscribing…");

        $success = 0;
        $failed  = 0;

        // Deduplicate by waba_id — one subscription call per WABA is enough
        $processedWabas = [];

        foreach ($numbers as $wu) {
            $wabaId = (string) $wu->waba_id;
            $accessToken = (string) $wu->access_token;

            if (isset($processedWabas[$wabaId])) {
                $this->line("  WABA {$wabaId} (number: {$wu->number}) — already processed, skipping duplicate");
                continue;
            }

            $processedWabas[$wabaId] = true;

            $this->line("  Processing WABA {$wabaId} (number: {$wu->number}, user_id: {$wu->user_id})…");

            if ($dryRun) {
                $this->line("    [DRY RUN] Would call subscribeAppToWaba for WABA {$wabaId}");
                $success++;
                continue;
            }

            try {
                $this->graphService->subscribeAppToWaba($accessToken, $wabaId);
                $this->info("    ✓ Subscribed WABA {$wabaId}");
                $success++;

                if ($verify) {
                    $fields = $this->readSubscribedFields($accessToken, $wabaId);
                    $this->line("    Subscribed fields: " . implode(', ', $fields));

                    $hasEchoes = in_array('message_echoes', $fields, true);
                    $hasSmbEchoes = in_array('smb_message_echoes', $fields, true);

                    if ($hasEchoes && $hasSmbEchoes) {
                        $this->info("    ✓ message_echoes and smb_message_echoes confirmed");
                    } else {
                        $this->warn("    ⚠ Echo fields not confirmed in subscription response");
                    }
                }
            } catch (\Throwable $e) {
                $this->error("    ✗ Failed for WABA {$wabaId}: " . $e->getMessage());
                Log::error('wa:resubscribe-echoes.failed', [
                    'waba_id' => $wabaId,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done. Success: {$success}, Failed: {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Read back the currently subscribed fields for a WABA from the Graph API.
     *
     * @return string[]
     */
    private function readSubscribedFields(string $accessToken, string $wabaId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get("https://graph.facebook.com/v20.0/{$wabaId}/subscribed_apps");

            $data = $response->json('data', []);
            return $data[0]['subscribed_fields'] ?? [];
        } catch (\Throwable $e) {
            Log::warning('wa:resubscribe-echoes.verify_failed', ['waba_id' => $wabaId, 'error' => $e->getMessage()]);
            return [];
        }
    }
}
