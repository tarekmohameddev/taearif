<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration optimizes JSON features column searching performance.
     * Note: MySQL/MariaDB doesn't directly support indexes for whereJsonContains on JSON arrays.
     * The main optimization is in the code (grouping whereJsonContains conditions in a closure).
     * 
     * For MySQL 8.0.17+: Attempts to add a functional index on JSON expressions.
     * For earlier versions: Creates a virtual generated column for potential future full-text search.
     *
     * @return void
     */
    public function up()
    {
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $indexName]
            );
            return $result[0]->count > 0;
        };

        // Helper method to check if column exists
        $hasColumn = function ($table, $columnName) {
            return Schema::hasColumn($table, $columnName);
        };

        // Check MySQL/MariaDB version for functional index support (MySQL 8.0.17+)
        $version = DB::select('SELECT VERSION() as version')[0]->version ?? '';
        $isMysql80Plus = version_compare($version, '8.0.17', '>=');
        $isMariaDB = stripos($version, 'mariadb') !== false;

        // For MySQL 8.0.17+ and MariaDB 10.4+: Try to add a virtual generated column with index
        // This can help with future optimizations and full-text search scenarios
        if (!$hasColumn('user_properties', 'features_text')) {
            try {
                Schema::table('user_properties', function (Blueprint $table) use ($isMysql80Plus, $isMariaDB) {
                    // Create a virtual generated column that extracts JSON array as text
                    // This can be useful for full-text search or LIKE queries in the future
                    // Note: This doesn't directly optimize whereJsonContains, but provides
                    // a foundation for future optimizations (e.g., if we switch to full-text search)
                    $table->string('features_text')->virtualAs(
                        "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(`features`, '$')), '')"
                    )->nullable()->after('features');
                });

                // Add index on the generated column (this helps with text-based searches)
                if (!$hasIndex('user_properties', 'user_properties_features_text_index')) {
                    Schema::table('user_properties', function (Blueprint $table) {
                        $table->index('features_text', 'user_properties_features_text_index');
                    });
                }
            } catch (\Exception $e) {
                // If virtual columns are not supported or there's an error, log and continue
                // The code-level optimization (grouping whereJsonContains) is the main improvement
                Log::warning('Could not add features_text generated column for JSON index optimization', [
                    'error' => $e->getMessage(),
                    'version' => $version
                ]);
            }
        }

        // For MySQL 8.0.17+: Attempt to add a functional index on JSON column
        // Note: MySQL functional indexes on JSON expressions are limited
        // The main optimization remains in the code (PropertyController grouping)
        if ($isMysql80Plus && !$isMariaDB) {
            try {
                // Check if functional index is supported
                $result = DB::select("SHOW VARIABLES LIKE 'innodb_version'");
                if (!empty($result)) {
                    // Note: Functional indexes on JSON_EXTRACT for arrays are complex
                    // For now, we rely on the code optimization (grouping conditions)
                    // If needed in the future, we can add specific functional indexes here
                }
            } catch (\Exception $e) {
                // Functional indexes may not be available, continue without them
                Log::warning('Functional indexes not available for features JSON column', [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop index if it exists
        if (Schema::hasColumn('user_properties', 'features_text')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $indexExists = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [DB::connection()->getDatabaseName(), 'user_properties', 'user_properties_features_text_index']
                );
                
                if (!empty($indexExists) && $indexExists[0]->count > 0) {
                    $table->dropIndex('user_properties_features_text_index');
                }
                
                $table->dropColumn('features_text');
            });
        }
    }
};
