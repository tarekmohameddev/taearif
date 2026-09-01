<?php

namespace App\Console\Commands;

use App\Models\Api\ApiDomainSetting;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcileVercelDomainsCommand extends Command
{
    protected $signature = 'domains:reconcile-vercel
                            {--fix : Apply safe fixes (Vercel-only orphans only)}
                            {--json : Machine-readable output}
                            {--force-production : Allow --fix outside production APP_ENV}';

    protected $description = 'Report mismatches between api_domains_settings and Vercel project domains';

    public function handle(VercelDomainClient $vercel): int
    {
        if (! $vercel->isConfigured()) {
            $this->error('Vercel is not configured (VERCEL_TOKEN / VERCEL_PROJECT_ID).');

            return self::FAILURE;
        }

        $applyFix = (bool) $this->option('fix');

        if ($applyFix && ! $this->fixAllowed()) {
            $this->error('--fix requires APP_ENV=production or --force-production to prevent staging accidents.');

            return self::FAILURE;
        }

        try {
            $vercelNames = $vercel->listProjectDomainNames();
        } catch (VercelDomainException $e) {
            $this->error('Failed to list Vercel project domains: ' . $e->getMessage());

            return self::FAILURE;
        }

        $report = $this->buildReport($vercel, $vercelNames);

        $fixesApplied = [];

        if ($applyFix) {
            $fixesApplied = $this->applyFixes($vercel, $report['vercel_only']);
            $report['fixes_applied'] = $fixesApplied;
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->printReport($report, $applyFix);
        }

        Log::info('domains:reconcile-vercel complete', [
            'summary' => $report['summary'],
            'fix' => $applyFix,
            'fixes_applied' => count($fixesApplied),
        ]);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $vercelNames
     * @return array{
     *   summary: array<string, int>,
     *   db_only: list<array<string, mixed>>,
     *   vercel_only: list<array<string, mixed>>,
     *   unpaired_www: list<array<string, mixed>>,
     *   status_mismatch: list<array<string, mixed>>,
     *   legacy_table_orphan: list<array<string, mixed>>
     * }
     */
    private function buildReport(VercelDomainClient $vercel, array $vercelNames): array
    {
        $vercelSet = array_fill_keys($vercelNames, true);

        $dbRows = ApiDomainSetting::query()
            ->select(['id', 'custom_name', 'status', 'dns_records', 'custom_domain_id'])
            ->orderBy('id')
            ->get();

        $dbApexMap = [];
        foreach ($dbRows as $row) {
            $apex = $vercel->normalizeApex((string) $row->custom_name);
            $dbApexMap[$apex][] = $row;
        }

        $dbOnly = [];
        foreach ($dbRows as $row) {
            $apex = $vercel->normalizeApex((string) $row->custom_name);
            if (! isset($vercelSet[$apex])) {
                $dbOnly[] = [
                    'id' => $row->id,
                    'custom_name' => $row->custom_name,
                    'apex' => $apex,
                    'status' => $row->status,
                ];
            }
        }

        $vercelOnlyByApex = [];
        foreach ($vercelNames as $name) {
            $apex = $vercel->normalizeApex($name);
            if (! isset($dbApexMap[$apex])) {
                $vercelOnlyByApex[$apex][] = $name;
            }
        }

        $vercelOnly = [];
        foreach ($vercelOnlyByApex as $apex => $names) {
            $vercelOnly[] = [
                'apex' => $apex,
                'vercel_names' => array_values(array_unique($names)),
            ];
        }

        $unpairedWww = $this->detectUnpairedWww($vercelNames, $vercelSet);

        $statusMismatch = [];
        foreach ($dbRows as $row) {
            if ($row->status !== 'active') {
                continue;
            }

            $dnsRecords = is_array($row->dns_records) ? $row->dns_records : [];
            $lastCheck = $dnsRecords['last_check'] ?? null;

            if (! is_array($lastCheck)) {
                continue;
            }

            if (($lastCheck['vercel_attached'] ?? null) === false) {
                $statusMismatch[] = [
                    'id' => $row->id,
                    'custom_name' => $row->custom_name,
                    'status' => $row->status,
                    'vercel_attached' => false,
                ];
            }
        }

        $legacyOrphans = DB::table('user_custom_domains as ucd')
            ->leftJoin('api_domains_settings as ads', 'ads.custom_domain_id', '=', 'ucd.id')
            ->whereNull('ads.id')
            ->select([
                'ucd.id',
                'ucd.user_id',
                'ucd.requested_domain',
                'ucd.current_domain',
            ])
            ->orderBy('ucd.id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'user_id' => $row->user_id,
                'requested_domain' => $row->requested_domain,
                'current_domain' => $row->current_domain,
            ])
            ->all();

        return [
            'summary' => [
                'db_only' => count($dbOnly),
                'vercel_only' => count($vercelOnly),
                'unpaired_www' => count($unpairedWww),
                'status_mismatch' => count($statusMismatch),
                'legacy_table_orphan' => count($legacyOrphans),
            ],
            'db_only' => $dbOnly,
            'vercel_only' => $vercelOnly,
            'unpaired_www' => $unpairedWww,
            'status_mismatch' => $statusMismatch,
            'legacy_table_orphan' => $legacyOrphans,
        ];
    }

    /**
     * @param  list<string>  $vercelNames
     * @param  array<string, true>  $vercelSet
     * @return list<array{vercel_name: string, missing: string, type: string}>
     */
    private function detectUnpairedWww(array $vercelNames, array $vercelSet): array
    {
        $unpaired = [];
        $seen = [];

        foreach ($vercelNames as $name) {
            if (str_starts_with($name, 'www.')) {
                $apex = substr($name, 4);
                if ($apex === '' || isset($vercelSet[$apex])) {
                    continue;
                }
                $key = "www:{$name}";
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $unpaired[] = [
                    'vercel_name' => $name,
                    'missing' => $apex,
                    'type' => 'www_without_apex',
                ];
            } else {
                $www = 'www.' . $name;
                if (isset($vercelSet[$www])) {
                    continue;
                }
                $key = "apex:{$name}";
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $unpaired[] = [
                    'vercel_name' => $name,
                    'missing' => $www,
                    'type' => 'apex_without_www',
                ];
            }
        }

        return $unpaired;
    }

    /**
     * @param  list<array{apex: string, vercel_names: list<string>}>  $vercelOnly
     * @return list<array{apex: string, status: string, error?: string}>
     */
    private function applyFixes(VercelDomainClient $vercel, array $vercelOnly): array
    {
        $results = [];

        foreach ($vercelOnly as $entry) {
            $apex = $entry['apex'];

            try {
                $vercel->removeApexAndWww($apex);
                Log::info('domains:reconcile-vercel removed Vercel-only orphan', [
                    'apex' => $apex,
                    'vercel_names' => $entry['vercel_names'],
                ]);
                $results[] = [
                    'apex' => $apex,
                    'status' => 'removed',
                ];
            } catch (Throwable $e) {
                Log::error('domains:reconcile-vercel failed to remove Vercel-only orphan', [
                    'apex' => $apex,
                    'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'apex' => $apex,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @param  array{
     *   summary: array<string, int>,
     *   db_only: list<array<string, mixed>>,
     *   vercel_only: list<array<string, mixed>>,
     *   unpaired_www: list<array<string, mixed>>,
     *   status_mismatch: list<array<string, mixed>>,
     *   legacy_table_orphan: list<array<string, mixed>>,
     *   fixes_applied?: list<array<string, mixed>>
     * }  $report
     */
    private function printReport(array $report, bool $applyFix): void
    {
        $this->info('Vercel domain reconciliation report');
        $this->newLine();

        $this->table(
            ['Category', 'Count'],
            collect($report['summary'])->map(fn ($count, $category) => [$category, $count])->values()->all()
        );

        $this->printSection('db_only (in DB, not on Vercel)', $report['db_only'], ['id', 'custom_name', 'apex', 'status']);
        $this->printVercelOnlySection($report['vercel_only']);
        $this->printSection('unpaired_www', $report['unpaired_www'], ['vercel_name', 'missing', 'type']);
        $this->printSection('status_mismatch (active but vercel_attached=false)', $report['status_mismatch'], ['id', 'custom_name', 'status']);
        $this->printSection('legacy_table_orphan (user_custom_domains without api_domains_settings)', $report['legacy_table_orphan'], ['id', 'user_id', 'requested_domain', 'current_domain']);

        if ($applyFix && ! empty($report['fixes_applied'] ?? [])) {
            $this->newLine();
            $this->info('Fixes applied (--fix):');
            $this->printSection('', $report['fixes_applied'], ['apex', 'status', 'error']);
        } elseif ($applyFix) {
            $this->newLine();
            $this->comment('No Vercel-only orphans to remove.');
        }

        if (! $applyFix && $report['summary']['vercel_only'] > 0) {
            $this->newLine();
            $this->comment('Run with --fix (production only) to remove confirmed Vercel-only orphans.');
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
            return array_map(fn (string $col) => (string) ($row[$col] ?? ''), $columns);
        }, $rows);

        $this->table($columns, $tableRows);
    }

    /**
     * @param  list<array{apex: string, vercel_names: list<string>}>  $rows
     */
    private function printVercelOnlySection(array $rows): void
    {
        $this->newLine();
        $this->line('<fg=yellow>vercel_only (on Vercel, not in DB)</> (' . $this->countLabel(count($rows)) . ')');

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

    private function fixAllowed(): bool
    {
        return app()->environment('production') || (bool) $this->option('force-production');
    }
}
