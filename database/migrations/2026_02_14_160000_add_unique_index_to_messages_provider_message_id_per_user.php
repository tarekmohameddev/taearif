<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        $singleColumnIndex = $this->findIndexByColumns('messages', ['provider_message_id'], false);
        if ($singleColumnIndex !== null) {
            Schema::table('messages', function (Blueprint $table) use ($singleColumnIndex) {
                $table->dropIndex($singleColumnIndex);
            });
        }

        $compositeUnique = $this->findIndexByColumns('messages', ['user_id', 'provider_message_id'], true);
        if ($compositeUnique === null) {
            Schema::table('messages', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'provider_message_id'],
                    'messages_user_id_provider_message_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('messages')) {
            return;
        }

        $compositeUnique = $this->findIndexByColumns('messages', ['user_id', 'provider_message_id'], true);
        if ($compositeUnique !== null) {
            Schema::table('messages', function (Blueprint $table) use ($compositeUnique) {
                $table->dropUnique($compositeUnique);
            });
        }

        $singleColumnIndex = $this->findIndexByColumns('messages', ['provider_message_id'], false);
        if ($singleColumnIndex === null && Schema::hasColumn('messages', 'provider_message_id')) {
            Schema::table('messages', function (Blueprint $table) {
                $table->index('provider_message_id', 'messages_provider_message_id_index');
            });
        }
    }

    private function findIndexByColumns(string $table, array $columns, bool $unique): ?string
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return null;
        }
        $indexes = DB::select("SHOW INDEX FROM `{$table}`");
        if (empty($indexes)) {
            return null;
        }

        $grouped = [];
        foreach ($indexes as $index) {
            $name = (string) $index->Key_name;
            $grouped[$name]['unique'] = ((int) $index->Non_unique) === 0;
            $grouped[$name]['columns'][(int) $index->Seq_in_index] = (string) $index->Column_name;
        }

        foreach ($grouped as $name => $data) {
            if ($data['unique'] !== $unique) {
                continue;
            }
            ksort($data['columns']);
            $indexedColumns = array_values($data['columns']);
            if ($indexedColumns === $columns) {
                return $name;
            }
        }

        return null;
    }
};
