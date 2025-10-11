<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User\RealestateManagement\ProjectContent;
use Carbon\Carbon;

class FixDuplicateProjectSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:fix-duplicate-slugs
                            {--dry-run : Preview changes without applying them}
                            {--force : Skip confirmation and apply changes automatically}
                            {--strategy=number : Fix strategy: number, id, or timestamp}
                            {--user-id= : Fix duplicates for specific user only}
                            {--keep=oldest : Which duplicate to keep: oldest or newest}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find and fix duplicate project slugs with flexible strategies';

    protected $fixedCount = 0;
    protected $logFile;
    protected $changes = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->logFile = storage_path('logs/slug-fixes-' . date('Y-m-d-His') . '.log');

        $this->info('🔍 Scanning for duplicate project slugs...');
        $this->newLine();

        // Find duplicates
        $duplicates = $this->findDuplicates();

        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicate slugs found! Database is clean.');
            return Command::SUCCESS;
        }

        // Display duplicates
        $this->displayDuplicates($duplicates);

        // Dry run mode
        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('🔍 DRY RUN MODE - No changes will be applied');
            $this->previewFixes($duplicates);
            return Command::SUCCESS;
        }

        // Confirm action
        if (!$this->option('force')) {
            $this->newLine();
            if (!$this->confirm('Do you want to fix these duplicate slugs?', true)) {
                $this->info('Operation cancelled.');
                return Command::SUCCESS;
            }
        }

        // Apply fixes
        $this->newLine();
        $this->info('🔧 Applying fixes...');
        $this->fixDuplicates($duplicates);

        // Summary
        $this->displaySummary();

        return Command::SUCCESS;
    }

    /**
     * Find all duplicate slugs
     */
    protected function findDuplicates()
    {
        $query = DB::table('user_project_contents')
            ->select('user_id', 'slug', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id', 'slug')
            ->having('count', '>', 1);

        // Filter by user if specified
        if ($userId = $this->option('user-id')) {
            $query->where('user_id', $userId);
        }

        $duplicates = $query->get();

        // Get full details for each duplicate group
        return $duplicates->map(function ($duplicate) {
            $contents = ProjectContent::where('user_id', $duplicate->user_id)
                ->where('slug', $duplicate->slug)
                ->with('project')
                ->orderBy('id', 'asc')
                ->get();

            return [
                'user_id' => $duplicate->user_id,
                'slug' => $duplicate->slug,
                'count' => $duplicate->count,
                'contents' => $contents,
            ];
        });
    }

    /**
     * Display duplicates in a table
     */
    protected function displayDuplicates($duplicates)
    {
        $totalDuplicates = $duplicates->sum('count');
        $totalGroups = $duplicates->count();

        $this->warn("⚠️  Found {$totalDuplicates} duplicate entries in {$totalGroups} slug groups");
        $this->newLine();

        foreach ($duplicates as $index => $duplicate) {
            $this->line("Group " . ($index + 1) . ":");
            $this->table(
                ['ID', 'Project ID', 'User ID', 'Slug', 'Title', 'Created At'],
                $duplicate['contents']->map(fn($content) => [
                    $content->id,
                    $content->project_id,
                    $content->user_id,
                    $content->slug,
                    $content->title ?? 'N/A',
                    $content->created_at?->format('Y-m-d H:i'),
                ])->toArray()
            );
            $this->newLine();
        }
    }

    /**
     * Preview what changes would be made
     */
    protected function previewFixes($duplicates)
    {
        $strategy = $this->option('strategy');
        $keep = $this->option('keep');

        $this->info("Strategy: {$strategy}");
        $this->info("Keep: {$keep} duplicate");
        $this->newLine();

        foreach ($duplicates as $index => $duplicate) {
            $this->line("Group " . ($index + 1) . " - Slug: {$duplicate['slug']}");

            $contents = $keep === 'oldest'
                ? $duplicate['contents']
                : $duplicate['contents']->reverse();

            $changes = [];
            foreach ($contents as $contentIndex => $content) {
                if ($contentIndex === 0) {
                    // Keep first one unchanged
                    $changes[] = [
                        'ID' => $content->id,
                        'Current Slug' => $content->slug,
                        'New Slug' => $content->slug . ' (unchanged)',
                        'Action' => '✓ Keep',
                    ];
                } else {
                    $newSlug = $this->generateNewSlug($content->slug, $contentIndex + 1, $content, $strategy);
                    $changes[] = [
                        'ID' => $content->id,
                        'Current Slug' => $content->slug,
                        'New Slug' => $newSlug,
                        'Action' => '→ Rename',
                    ];
                }
            }

            $this->table(['ID', 'Current Slug', 'New Slug', 'Action'], $changes);
            $this->newLine();
        }
    }

    /**
     * Fix duplicate slugs
     */
    protected function fixDuplicates($duplicates)
    {
        $strategy = $this->option('strategy');
        $keep = $this->option('keep');

        DB::transaction(function () use ($duplicates, $strategy, $keep) {
            foreach ($duplicates as $duplicate) {
                $contents = $keep === 'oldest'
                    ? $duplicate['contents']
                    : $duplicate['contents']->reverse();

                foreach ($contents as $contentIndex => $content) {
                    if ($contentIndex === 0) {
                        // Keep first one unchanged
                        $this->logChange($content->id, $content->slug, $content->slug, 'kept');
                        continue;
                    }

                    $oldSlug = $content->slug;
                    $newSlug = $this->generateNewSlug($oldSlug, $contentIndex + 1, $content, $strategy);

                    // Update the slug
                    $content->slug = $newSlug;
                    $content->save();

                    $this->fixedCount++;
                    $this->logChange($content->id, $oldSlug, $newSlug, 'renamed');

                    $this->line("✓ Fixed: Content ID {$content->id} - {$oldSlug} → {$newSlug}");
                }
            }
        });
    }

    /**
     * Generate new slug based on strategy
     */
    protected function generateNewSlug($baseSlug, $index, $content, $strategy)
    {
        switch ($strategy) {
            case 'id':
                return $baseSlug . '-' . $content->project_id;

            case 'timestamp':
                $year = $content->created_at ? $content->created_at->format('Y') : date('Y');
                return $baseSlug . '-' . $year;

            case 'number':
            default:
                return $baseSlug . '-' . $index;
        }
    }

    /**
     * Log changes for audit trail
     */
    protected function logChange($contentId, $oldSlug, $newSlug, $action)
    {
        $change = [
            'timestamp' => now()->toDateTimeString(),
            'content_id' => $contentId,
            'old_slug' => $oldSlug,
            'new_slug' => $newSlug,
            'action' => $action,
        ];

        $this->changes[] = $change;

        $logMessage = "[{$change['timestamp']}] Content ID: {$contentId} | Action: {$action} | {$oldSlug} → {$newSlug}";
        file_put_contents($this->logFile, $logMessage . PHP_EOL, FILE_APPEND);

        Log::info('Project slug fixed', $change);
    }

    /**
     * Display summary of operations
     */
    protected function displaySummary()
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('✅ OPERATION COMPLETED');
        $this->info('═══════════════════════════════════════');
        $this->line("Fixed: {$this->fixedCount} duplicate slugs");
        $this->line("Strategy: {$this->option('strategy')}");
        $this->line("Keep: {$this->option('keep')} duplicate");
        $this->line("Log file: {$this->logFile}");
        $this->info('═══════════════════════════════════════');
        $this->newLine();

        if ($this->fixedCount > 0) {
            $this->warn('⚠️  IMPORTANT: Review the log file for details');
            $this->info('📋 Next step: Run migration again');
            $this->line('   php artisan migrate');
        }
    }
}

