<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixMigrationsTableCommand extends Command
{
    protected $signature = 'migrate:fix-migrations-table';
    protected $description = 'Fix migrations table: ensure id column is AUTO_INCREMENT (fixes "Field id doesn\'t have a default value" in testing)';

    public function handle(): int
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql' && $driver !== 'mariadb') {
            $this->warn("This command only supports MySQL/MariaDB. Current driver: {$driver}");
            return 1;
        }

        if (!Schema::hasTable('migrations')) {
            $this->warn('Table migrations does not exist. Run php artisan migrate first.');
            return 0;
        }

        $table = 'migrations';
        $column = 'id';

        // Check if id already has AUTO_INCREMENT (MySQL)
        $result = DB::selectOne(
            "SELECT COLUMN_TYPE, EXTRA FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?",
            [DB::getDatabaseName(), $table, $column]
        );

        if ($result && str_contains((string) ($result->EXTRA ?? ''), 'auto_increment')) {
            $this->info('migrations.id already has AUTO_INCREMENT. Nothing to do.');
            return 0;
        }

        $this->info('Fixing migrations table: setting id to AUTO_INCREMENT...');

        try {
            // Laravel's default migrations table uses increments() => INT UNSIGNED
            DB::statement('ALTER TABLE migrations MODIFY id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY');
            $this->info('Done. You can run php artisan migrate again.');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());
            return 1;
        }
    }
}
