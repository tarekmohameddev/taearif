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
     * Adds full-text search indexes to optimize search queries on user_property_contents table.
     * Full-text indexes improve performance for text searches on title, address, and description.
     * 
     * Note: Requires MySQL 5.6+ with InnoDB or MyISAM engine.
     * For InnoDB, full-text indexes are supported starting from MySQL 5.6.4.
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

        // Check MySQL version and engine support
        try {
            $version = DB::select('SELECT VERSION() as version')[0]->version ?? '';
            $isMysql56Plus = version_compare($version, '5.6.4', '>=');
            
            // Check table engine
            $tableInfo = DB::select("SHOW TABLE STATUS WHERE Name = 'user_property_contents'");
            $engine = $tableInfo[0]->Engine ?? '';
            $supportsFulltext = $isMysql56Plus && in_array(strtolower($engine), ['innodb', 'myisam']);
            
            if ($supportsFulltext) {
                // Add full-text index on title, address, and description for better search performance
                if (!$hasIndex('user_property_contents', 'ft_prop_content_search')) {
                    try {
                        DB::statement('ALTER TABLE user_property_contents ADD FULLTEXT INDEX ft_prop_content_search (title, address, description(500))');
                    } catch (\Exception $e) {
                        Log::warning('Could not add full-text index for property contents search', [
                            'error' => $e->getMessage(),
                            'version' => $version,
                            'engine' => $engine
                        ]);
                    }
                }
            } else {
                Log::info('Skipping full-text index creation - requires MySQL 5.6.4+ with InnoDB/MyISAM', [
                    'version' => $version,
                    'engine' => $engine
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Error checking MySQL version/engine for full-text index', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        try {
            DB::statement('ALTER TABLE user_property_contents DROP INDEX ft_prop_content_search');
        } catch (\Exception $e) {
            // Index might not exist, continue
            Log::info('Full-text index ft_prop_content_search does not exist or could not be dropped', [
                'error' => $e->getMessage()
            ]);
        }
    }
};
