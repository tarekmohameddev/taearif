<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            return;
        }

        if (!Schema::hasColumn('idempotency_keys', 'endpoint')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->string('endpoint')->default('unknown')->after('idempotency_key');
            });
        }

        DB::table('idempotency_keys')
            ->whereNull('endpoint')
            ->orWhere('endpoint', '')
            ->update(['endpoint' => 'unknown']);

        Schema::table('idempotency_keys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        $oldUnique = $this->findUniqueIndexByColumns('idempotency_keys', ['user_id', 'idempotency_key']);
        if ($oldUnique !== null) {
            Schema::table('idempotency_keys', function (Blueprint $table) use ($oldUnique) {
                $table->dropUnique($oldUnique);
            });
        }

        $newUnique = $this->findUniqueIndexByColumns('idempotency_keys', ['user_id', 'idempotency_key', 'endpoint']);
        if ($newUnique === null) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'idempotency_key', 'endpoint'],
                    'idempotency_keys_user_id_idempotency_key_endpoint_unique'
                );
            });
        }

        $supportingIndex = $this->findIndexByColumns('idempotency_keys', ['user_id', 'endpoint'], false);
        if ($supportingIndex === null) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->index(['user_id', 'endpoint'], 'idempotency_keys_user_id_endpoint_index');
            });
        }

        Schema::table('idempotency_keys', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            return;
        }

        Schema::table('idempotency_keys', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        $supportingIndex = $this->findIndexByColumns('idempotency_keys', ['user_id', 'endpoint'], false);
        if ($supportingIndex !== null) {
            Schema::table('idempotency_keys', function (Blueprint $table) use ($supportingIndex) {
                $table->dropIndex($supportingIndex);
            });
        }

        $newUnique = $this->findUniqueIndexByColumns('idempotency_keys', ['user_id', 'idempotency_key', 'endpoint']);
        if ($newUnique !== null) {
            Schema::table('idempotency_keys', function (Blueprint $table) use ($newUnique) {
                $table->dropUnique($newUnique);
            });
        }

        $hasDuplicates = DB::table('idempotency_keys')
            ->select('user_id', 'idempotency_key', DB::raw('COUNT(*) as aggregate_count'))
            ->groupBy('user_id', 'idempotency_key')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException(
                'Cannot rollback idempotency_keys unique scope: duplicate (user_id, idempotency_key) pairs exist.'
            );
        }

        $oldUnique = $this->findUniqueIndexByColumns('idempotency_keys', ['user_id', 'idempotency_key']);
        if ($oldUnique === null) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'idempotency_key'],
                    'idempotency_keys_user_id_idempotency_key_unique'
                );
            });
        }

        Schema::table('idempotency_keys', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        if (Schema::hasColumn('idempotency_keys', 'endpoint')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->dropColumn('endpoint');
            });
        }
    }

    private function findUniqueIndexByColumns(string $table, array $columns): ?string
    {
        return $this->findIndexByColumns($table, $columns, true);
    }

    private function findIndexByColumns(string $table, array $columns, bool $unique): ?string
    {
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
