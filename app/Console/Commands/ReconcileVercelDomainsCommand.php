<?php

namespace App\Console\Commands;

use App\Services\Vercel\DomainReconciliationService;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileVercelDomainsCommand extends Command
{
    protected $signature = 'domains:reconcile-vercel
                            {--remove-apex= : Remove one Vercel-only orphan apex (requires --confirm-apex)}
                            {--confirm-apex= : Exact apex confirmation for --remove-apex}
                            {--json : Machine-readable output}
                            {--force-production : Allow removal outside production APP_ENV}';

    protected $description = 'Report mismatches between api_domains_settings, legacy user_custom_domains, and Vercel project domains';

    public function handle(
        VercelDomainClient $vercel,
        DomainReconciliationService $reconciliation
    ): int {
        if (! $vercel->isConfigured()) {
            $this->error('Vercel is not configured (VERCEL_TOKEN / VERCEL_PROJECT_ID).');

            return self::FAILURE;
        }

        $removeApex = $this->option('remove-apex');
        $confirmApex = $this->option('confirm-apex');

        if ($removeApex !== null) {
            if ($confirmApex === null) {
                $this->error('--remove-apex requires --confirm-apex with the exact apex name.');

                return self::FAILURE;
            }

            if (! $this->removalAllowed()) {
                $this->error('Removal requires APP_ENV=production or --force-production to prevent staging accidents.');

                return self::FAILURE;
            }

            return $this->handleRemoval($reconciliation, (string) $removeApex, (string) $confirmApex);
        }

        if ($confirmApex !== null) {
            $this->error('--confirm-apex is only valid together with --remove-apex.');

            return self::FAILURE;
        }

        try {
            $report = $reconciliation->buildReport(fetchFresh: true);
        } catch (VercelDomainException $e) {
            $this->error('Failed to list Vercel project domains: ' . $e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->printReport($report);
        }

        Log::info('domains:reconcile-vercel complete', [
            'summary' => $report['summary'],
            'mode' => 'report',
        ]);

        return self::SUCCESS;
    }

    private function handleRemoval(
        DomainReconciliationService $reconciliation,
        string $apex,
        string $confirmApex
    ): int {
        try {
            $result = $reconciliation->removeVercelOnlyOrphan(
                $apex,
                confirmedApex: $confirmApex,
                actor: 'console:domains:reconcile-vercel'
            );
        } catch (VercelDomainException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode(['removal' => $result], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            if ($result['status'] === 'removed') {
                $this->info("Removed Vercel-only orphan: {$result['apex']}");
            } else {
                $this->error("Failed to remove {$result['apex']}: " . ($result['error'] ?? 'unknown error'));
            }
        }

        Log::info('domains:reconcile-vercel removal complete', [
            'apex' => $result['apex'],
            'status' => $result['status'],
        ]);

        return $result['status'] === 'removed' ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function printReport(array $report): void
    {
        $this->info('Vercel domain reconciliation report');
        $this->newLine();

        $this->table(
            ['Category', 'Count'],
            collect($report['summary'])->map(fn ($count, $category) => [$category, $count])->values()->all()
        );

        $this->printSection('db_only (in DB, not on Vercel)', $report['db_only'], ['id', 'custom_name', 'apex', 'status']);
        $this->printVercelOnlySection($report['vercel_only_orphan']);
        $this->printSection('protected_platform', $report['protected_platform'], ['apex', 'vercel_names']);
        $this->printSection('apex_with_optional_www (linked)', $report['apex_with_optional_www'], ['apex', 'has_www', 'www_redirect_correct']);
        $this->printSection('www_without_apex', $report['www_without_apex'], ['vercel_name', 'missing', 'type']);
        $this->printSection('apex_without_www (informational)', $report['apex_without_www'], ['apex', 'type']);
        $this->printSection('incorrect_redirect', $report['incorrect_redirect'], ['apex', 'vercel_name', 'issue']);
        $this->printSection('status_mismatch (active but vercel_attached=false)', $report['status_mismatch'], ['id', 'custom_name', 'status']);
        $this->printSection('legacy_table_orphan (user_custom_domains without api_domains_settings)', $report['legacy_table_orphan'], ['id', 'user_id', 'requested_domain', 'current_domain']);

        if ($report['summary']['vercel_only_orphan'] > 0) {
            $this->newLine();
            $this->comment('To remove one orphan at a time, run with --remove-apex=example.com --confirm-apex=example.com (production only).');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $columns
     */
    private function printSection(string $title, array $rows, array $columns): void
    {
        if ($title !== '') {
            $this->newLine();
            $this->line("<fg=yellow>{$title}</> ({$this->countLabel(count($rows))})");
        }

        if ($rows === []) {
            $this->line('  (none)');

            return;
        }

        $tableRows = array_map(function (array $row) use ($columns) {
            return array_map(function (string $col) use ($row) {
                $value = $row[$col] ?? '';
                if (is_array($value)) {
                    return implode(', ', $value);
                }
                if (is_bool($value)) {
                    return $value ? 'true' : 'false';
                }

                return (string) $value;
            }, $columns);
        }, $rows);

        $this->table($columns, $tableRows);
    }

    /**
     * @param  list<array{apex: string, vercel_names: list<string>}>  $rows
     */
    private function printVercelOnlySection(array $rows): void
    {
        $this->newLine();
        $this->line('<fg=yellow>vercel_only_orphan (on Vercel, not in DB or legacy)</> (' . $this->countLabel(count($rows)) . ')');

        if ($rows === []) {
            $this->line('  (none)');

            return;
        }

        $tableRows = array_map(
            fn (array $row) => [$row['apex'], implode(', ', $row['vercel_names'])],
            $rows
        );

        $this->table(['apex', 'vercel_names'], $tableRows);
    }

    private function countLabel(int $count): string
    {
        return $count === 1 ? '1 item' : "{$count} items";
    }

    private function removalAllowed(): bool
    {
        return app()->environment('production') || (bool) $this->option('force-production');
    }
}
