<?php

namespace App\Console\Commands;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\Language;
use App\Support\PropertyCompletionRequirements;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixLegacyIncompleteProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-legacy-incomplete-properties 
                            {--dry-run : Show what would be fixed without making changes}
                            {--force : Force migration without confirmation}
                            {--chunk=100 : Number of properties to process per chunk}
                            {--only-complete : Only target completion_status = "complete" (exclude NULL)}
                            {--update-status=1 : When 1, set completion_status to incomplete; when 0, only update missing_fields}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix legacy properties that are marked as complete but are missing required fields';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk');
        $onlyComplete = $this->option('only-complete');
        $updateStatus = (string) $this->option('update-status') !== '0';

        $this->info('Checking for legacy incomplete properties...');
        $this->newLine();

        $baseQuery = function () use ($onlyComplete) {
            if ($onlyComplete) {
                return Property::where('completion_status', 'complete');
            }
            return Property::where(function ($q) {
                $q->where('completion_status', 'complete')
                  ->orWhereNull('completion_status');
            });
        };

        $scopeDesc = $onlyComplete ? 'completion_status = "complete"' : 'completion_status = "complete" or NULL';
        $totalQuery = $baseQuery();
        $totalCount = $totalQuery->count();

        if ($totalCount === 0) {
            $this->info("✓ No properties found with {$scopeDesc}. Database is clean!");
            return self::SUCCESS;
        }

        $this->info("Found {$totalCount} property(ies) to check:");
        $this->newLine();

        // Get sample properties to show preview
        $sampleProperties = $totalQuery->limit(10)
            ->with(['contents' => function($q) {
                $q->select('id', 'property_id', 'language_id', 'title', 'address', 'description');
            }])
            ->get(['id', 'user_id', 'price', 'purpose', 'property_type', 'area', 'featured_image', 'completion_status']);

        $incompleteProperties = [];
        $sampleData = [];

        foreach ($sampleProperties as $property) {
            // Get default language for this property's owner
            $defaultLanguage = Language::where('user_id', $property->user_id)
                ->where('is_default', 1)
                ->first();

            if (!$defaultLanguage) {
                continue;
            }

            // Get property content for default language
            $propertyContent = $property->contents
                ->where('language_id', $defaultLanguage->id)
                ->first();

            // Check current data
            $currentData = [
                'title' => $propertyContent?->title,
                'price' => $property->price,
                'address' => $propertyContent?->address,
                'description' => $propertyContent?->description,
                'purpose' => $property->purpose,
                'property_type' => $property->property_type,
                'area' => $property->area,
            ];

            // Identify missing fields
            $missingFields = [];
            foreach ($this->requiredFields as $field) {
                $value = $currentData[$field] ?? null;
                if (is_null($value) || (is_string($value) && trim($value) === '') || $value === '') {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                $incompleteProperties[] = $property;
                $sampleData[] = [
                    'ID' => $property->id,
                    'User ID' => $property->user_id,
                    'Title' => $propertyContent?->title ?? 'NULL',
                    'Address' => $propertyContent?->address ?? 'NULL',
                    'Type' => $property->property_type ?? 'NULL',
                    'Area' => $property->area ?? 'NULL',
                    'Missing Fields' => implode(', ', $missingFields),
                ];
            }
        }

        if (empty($sampleData)) {
            $this->info('✓ Sample check: No incomplete properties found in first 10 properties.');
            $this->info('Checking all properties...');
            $this->newLine();
        } else {
            $this->warn("Found " . count($sampleData) . " incomplete property(ies) in sample (showing first 10):");
            $this->newLine();

            $this->table(
                ['ID', 'User ID', 'Title', 'Address', 'Type', 'Area', 'Missing Fields'],
                $sampleData
            );
            $this->newLine();
        }

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->info('Scanning all properties to show what would be fixed...');
            $this->newLine();

            // Count all incomplete properties
            $incompleteCount = 0;
            $this->output->progressStart($totalCount);

            $baseQuery()
                ->chunkById($chunkSize, function ($properties) use (&$incompleteCount) {
                    foreach ($properties as $property) {
                        if ($this->isPropertyIncomplete($property)) {
                            $incompleteCount++;
                        }
                        $this->output->progressAdvance();
                    }
                });

            $this->output->progressFinish();
            $this->newLine();
            $this->info(
                $updateStatus
                    ? "Would mark {$incompleteCount} property(ies) as incomplete"
                    : "Would update missing_fields for {$incompleteCount} property(ies)"
            );
            $this->info('Run without --dry-run to perform the migration');
            return self::SUCCESS;
        }

        // Ask for confirmation
        if (!$force) {
            $this->warn(
                $updateStatus
                    ? 'This will update properties marked as "complete" to "incomplete" if they are missing required fields.'
                    : 'This will update missing_fields only (completion_status will not be changed) for properties missing required fields.'
            );
            if (!$this->confirm('Do you want to proceed?', false)) {
                $this->info('Migration cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Starting migration...');
        $this->newLine();

        $fixedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $warningCount = 0;
        $processedCount = 0;

        // Process in chunks to avoid memory issues
        // Select only fields we need to avoid loading large JSON features that might trigger features_text virtual column issues
        $query = $baseQuery()
            ->select(['id', 'user_id', 'price', 'purpose', 'property_type', 'area', 'featured_image', 'completion_status']);

        $totalToProcess = $query->count();
        $this->output->progressStart($totalToProcess);

        $query->chunkById($chunkSize, function ($properties) use (
            &$fixedCount,
            &$skippedCount,
            &$errorCount,
            &$warningCount,
            &$processedCount,
            $updateStatus
        ) {
            foreach ($properties as $property) {
                try {
                    if (!$this->isPropertyIncomplete($property)) {
                        $skippedCount++;
                        $processedCount++;
                        $this->output->progressAdvance();
                        continue;
                    }

                    // Get missing fields
                    $missingFields = $this->getMissingFields($property);

                    // Update property using raw SQL with SQL_MODE adjustment
                    // Temporarily disable strict mode to allow virtual column recalculation even if it exceeds size limit
                    // This is safe because we're only updating completion_status and missing_fields, not features
                    $missingFieldsJson = json_encode($missingFields);

                    DB::transaction(function () use ($property, $missingFieldsJson, $updateStatus) {
                        // Get current SQL mode
                        $sqlMode = DB::selectOne("SELECT @@SESSION.sql_mode as mode");
                        $originalMode = $sqlMode->mode ?? '';

                        try {
                            // Temporarily set SQL mode to allow truncation (removes strict mode)
                            $relaxedMode = str_replace(['STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES'], '', $originalMode);
                            $relaxedMode = trim(str_replace(',,', ',', $relaxedMode), ',');
                            DB::statement("SET SESSION sql_mode = ?", [$relaxedMode]);

                            if ($updateStatus) {
                                DB::statement(
                                    "UPDATE `user_properties` 
                                     SET `completion_status` = 'incomplete', 
                                         `missing_fields` = ?, 
                                         `updated_at` = NOW() 
                                     WHERE `id` = ?",
                                    [$missingFieldsJson, $property->id]
                                );
                            } else {
                                DB::statement(
                                    "UPDATE `user_properties` 
                                     SET `missing_fields` = ?, 
                                         `updated_at` = NOW() 
                                     WHERE `id` = ?",
                                    [$missingFieldsJson, $property->id]
                                );
                            }
                        } finally {
                            // Always restore original SQL mode
                            DB::statement("SET SESSION sql_mode = ?", [$originalMode]);
                        }
                    });

                    $fixedCount++;
                    $processedCount++;

                    Log::info('Fixed legacy incomplete property', [
                        'property_id' => $property->id,
                        'user_id' => $property->user_id,
                        'missing_fields' => $missingFields,
                        'old_status' => $property->completion_status ?? 'NULL',
                    ]);

                } catch (\Exception $e) {
                    $processedCount++;
                    
                    // Check if it's the features_text virtual column issue
                    $isFeaturesTextError = strpos($e->getMessage(), 'features_text') !== false || 
                                          strpos($e->getMessage(), 'String data, right truncated') !== false;
                    
                    if ($isFeaturesTextError) {
                        // This is due to features_text virtual column size limitation
                        // The property still has large features JSON that exceeds 255 chars
                        // We'll skip these and they can be handled separately by fixing the features column
                        $warningCount++;
                        $this->newLine();
                        $this->warn("  ⚠ Skipped Property ID {$property->id}: features_text virtual column size limit (features JSON too large)");
                        
                        Log::warning('Skipped legacy incomplete property - features_text virtual column size limit', [
                            'property_id' => $property->id,
                            'error' => $e->getMessage(),
                            'note' => 'Property has large features JSON that exceeds virtual column size limit. Consider reducing features JSON size or increasing virtual column size.',
                        ]);
                    } else {
                        $errorCount++;
                        $this->newLine();
                        $this->error("  ✗ Failed to fix Property ID {$property->id}: {$e->getMessage()}");
                        
                        Log::error('Failed to fix legacy incomplete property', [
                            'property_id' => $property->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $this->output->progressAdvance();
            }
        });

        $this->output->progressFinish();
        $this->newLine();
        $this->newLine();

        $this->info('Migration completed!');
        $this->info(
            $updateStatus
                ? "  ✓ Fixed (marked as incomplete): {$fixedCount}"
                : "  ✓ Fixed (missing_fields updated): {$fixedCount}"
        );
        $this->info("  ⊙ Skipped (already complete): {$skippedCount}");
        if ($warningCount > 0) {
            $this->warn("  ⚠ Warnings (features_text size limit): {$warningCount}");
        }
        if ($errorCount > 0) {
            $this->error("  ✗ Errors: {$errorCount}");
        }

        // Verify migration
        $incompleteAfter = Property::where('completion_status', 'incomplete')->count();
        $this->newLine();
        $this->info("Current incomplete properties count: {$incompleteAfter}");

        return self::SUCCESS;
    }

    /**
     * Check if a property is incomplete (missing required fields)
     *
     * @param Property $property
     * @return bool
     */
    protected function isPropertyIncomplete(Property $property): bool
    {
        $missingFields = $this->getMissingFields($property);
        return !empty($missingFields);
    }

    /**
     * Get missing required fields for a property
     *
     * @param Property $property
     * @return array
     */
    protected function getMissingFields(Property $property): array
    {
        // Get default language for this property's owner
        $defaultLanguage = Language::where('user_id', $property->user_id)
            ->where('is_default', 1)
            ->first();

        if (!$defaultLanguage) {
            // If no default language, mark as incomplete with all fields missing
            return PropertyCompletionRequirements::fields();
        }

        // Get property content for default language
        $propertyContent = PropertyContent::where('property_id', $property->id)
            ->where('language_id', $defaultLanguage->id)
            ->first();

        // Check current data
        $currentData = [
            'title' => $propertyContent?->title,
            'address' => $propertyContent?->address,
            'description' => $propertyContent?->description,
            'property_type' => $property->property_type,
            'featured_image' => $property->featured_image,
        ];

        // Use the helper to get missing fields
        return PropertyCompletionRequirements::missingFrom($currentData);
    }
}
