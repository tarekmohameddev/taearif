<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'messages_user_direction_created_at_index';

    private const COLUMNS = ['user_id', 'direction', 'created_at'];

    public function up(): void
    {
        if (! Schema::hasTable('messages') || $this->findIndexByColumns(self::COLUMNS) !== null) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE `messages` ADD INDEX `' . self::INDEX . '` '
                . '(`user_id`, `direction`, `created_at`), ALGORITHM=INPLACE, LOCK=NONE'
            );

            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->index(self::COLUMNS, self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('messages')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            Schema::table('messages', function (Blueprint $table) {
                $table->dropIndex(self::INDEX);
            });

            return;
        }

        $index = $this->findIndexByColumns(self::COLUMNS);
        if ($index === null) {
            return;
        }

        DB::statement(
            'ALTER TABLE `messages` DROP INDEX `' . str_replace('`', '``', $index) . '`, '
            . 'ALGORITHM=INPLACE, LOCK=NONE'
        );
    }

    private function findIndexByColumns(array $columns): ?string
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return null;
        }

        $grouped = [];

        foreach (DB::select('SHOW INDEX FROM `messages`') as $index) {
            $name = (string) $index->Key_name;
            $grouped[$name][(int) $index->Seq_in_index] = (string) $index->Column_name;
        }

        foreach ($grouped as $name => $indexedColumns) {
            ksort($indexedColumns);

            if (array_values($indexedColumns) === $columns) {
                return $name;
            }
        }

        return null;
    }
};
