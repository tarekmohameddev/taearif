<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User\RealestateManagement\Property;
use App\Models\User;

class AnalyzePropertyIndexUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:analyze-indexes {--user-id= : Specific user ID to test with} {--detailed : Show detailed EXPLAIN output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze index usage for GET /api/properties endpoint queries';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Analyzing index usage for Property queries...');
        $this->newLine();

        // Get test user ID
        $userId = $this->option('user-id');
        if (!$userId) {
            $user = User::where('account_type', '!=', 'employee')->first();
            $userId = $user ? $user->id : 1;
            $this->warn("No user ID specified. Using user ID: {$userId}");
        }

        // Check required indexes
        $this->info('1. Checking required indexes...');
        $this->checkIndexes();
        $this->newLine();

        // Test common queries
        $this->info('2. Analyzing query performance with EXPLAIN...');
        $this->analyzeQueries($userId);
        $this->newLine();

        // Summary
        $this->info('Analysis complete!');

        return Command::SUCCESS;
    }

    /**
     * Check if required indexes exist
     */
    protected function checkIndexes(): void
    {
        $requiredIndexes = [
            'user_properties' => [
                'idx_user_reorder_composite' => 'Composite index for default sorting',
                'idx_user_created_at' => 'Composite index for date filtering',
                'user_properties_user_id_index' => 'Primary user_id index',
                'user_properties_category_id_index' => 'Category filter index',
                'user_properties_purpose_index' => 'Purpose filter index',
            ],
            'user_property_contents' => [
                'ft_prop_content_search' => 'Full-text search index',
                'idx_prop_content_city' => 'City filter composite index',
                'idx_prop_content_state' => 'State/district filter composite index',
                'idx_prop_content_property_id_id' => 'GROUP BY optimization index',
                'user_property_contents_property_id_index' => 'Property ID foreign key index',
            ],
        ];

        foreach ($requiredIndexes as $table => $indexes) {
            $this->line("  Table: {$table}");
            
            foreach ($indexes as $indexName => $description) {
                $exists = $this->indexExists($table, $indexName);
                $status = $exists ? '✓' : '✗';
                $color = $exists ? 'green' : 'red';
                
                $this->line("    {$status} {$indexName}: {$description}", null, null, $color);
            }
        }
    }

    /**
     * Check if an index exists
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            $databaseName = DB::getDatabaseName();
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $indexName]
            );
            return $result[0]->count > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Analyze common queries with EXPLAIN
     */
    protected function analyzeQueries(int $userId): void
    {
        $queries = [
            'Basic query with user_id filter' => function () use ($userId) {
                return Property::where('user_id', $userId)
                    ->orderBy('reorder_featured', 'desc')
                    ->orderBy('reorder', 'asc')
                    ->limit(20);
            },
            'Query with city filter (JOIN)' => function () use ($userId) {
                return Property::join('user_property_contents as pc_content', 'pc_content.property_id', '=', 'user_properties.id')
                    ->where('user_properties.user_id', $userId)
                    ->where('pc_content.city_id', 1)
                    ->groupBy('user_properties.id')
                    ->limit(20);
            },
            'Query with search filter (full-text)' => function () use ($userId) {
                return Property::join('user_property_contents as pc_content', 'pc_content.property_id', '=', 'user_properties.id')
                    ->where('user_properties.user_id', $userId)
                    ->whereRaw("MATCH(pc_content.title, pc_content.address, pc_content.description) AGAINST(? IN BOOLEAN MODE)", ['test'])
                    ->groupBy('user_properties.id')
                    ->limit(20);
            },
            'Query with multiple filters' => function () use ($userId) {
                return Property::where('user_id', $userId)
                    ->where('purpose', 'sale')
                    ->where('category_id', 1)
                    ->where('price', '>=', 100000)
                    ->orderBy('price', 'asc')
                    ->limit(20);
            },
        ];

        $detailed = $this->option('detailed');

        foreach ($queries as $queryName => $queryBuilder) {
            $this->line("  Query: {$queryName}");
            
            try {
                $query = $queryBuilder();
                $explainResults = $this->explainQuery($query);
                
                if (empty($explainResults)) {
                    $this->warn("    Could not explain query");
                    continue;
                }

                // Analyze EXPLAIN results
                $usesIndex = false;
                $possibleKeys = [];
                $key = null;
                $rows = 0;
                $extra = '';

                foreach ($explainResults as $result) {
                    if (!empty($result['possible_keys'])) {
                        $possibleKeys = array_merge($possibleKeys, explode(',', $result['possible_keys']));
                    }
                    if (!empty($result['key'])) {
                        $usesIndex = true;
                        $key = $result['key'];
                    }
                    $rows += (int) ($result['rows'] ?? 0);
                    $extra .= ($result['Extra'] ?? '') . ' ';
                }

                $possibleKeys = array_unique(array_filter($possibleKeys));

                // Report results
                if ($usesIndex && $key) {
                    $this->line("    ✓ Using index: {$key}", null, null, 'green');
                } else {
                    $this->line("    ✗ Not using index", null, null, 'red');
                    if (!empty($possibleKeys)) {
                        $this->line("    Available indexes: " . implode(', ', $possibleKeys), null, null, 'yellow');
                    }
                }

                $this->line("    Rows examined: {$rows}");
                
                if ($detailed) {
                    $this->line("    Extra: {$extra}");
                    $this->table(
                        ['Select Type', 'Type', 'Possible Keys', 'Key', 'Key Len', 'Ref', 'Rows', 'Extra'],
                        array_map(function ($result) {
                            return [
                                $result['select_type'] ?? '',
                                $result['type'] ?? '',
                                $result['possible_keys'] ?? '',
                                $result['key'] ?? '',
                                $result['key_len'] ?? '',
                                $result['ref'] ?? '',
                                $result['rows'] ?? '',
                                $result['Extra'] ?? '',
                            ];
                        }, $explainResults)
                    );
                }

            } catch (\Exception $e) {
                $this->error("    Error: {$e->getMessage()}");
            }
            
            $this->newLine();
        }
    }

    /**
     * Run EXPLAIN on a query
     */
    protected function explainQuery($query): array
    {
        try {
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            
            // Replace bindings for EXPLAIN
            $explainSql = str_replace('?', "'%s'", $sql);
            $explainSql = vsprintf($explainSql, array_map(function ($binding) {
                if (is_string($binding)) {
                    return "'" . addslashes($binding) . "'";
                }
                return (string) $binding;
            }, $bindings));
            
            $results = DB::select("EXPLAIN {$explainSql}");
            return array_map(function ($result) {
                return (array) $result;
            }, $results);
        } catch (\Exception $e) {
            $this->warn("    Could not explain query: {$e->getMessage()}");
            return [];
        }
    }
}
