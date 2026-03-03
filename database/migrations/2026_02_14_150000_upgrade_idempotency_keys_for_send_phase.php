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

        if (!Schema::hasColumn('idempotency_keys', 'status')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->string('status')->default('processing')->after('request_hash');
            });
        }

        if (!Schema::hasColumn('idempotency_keys', 'message_id')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->unsignedBigInteger('message_id')->nullable()->after('status');
                $table->index('message_id');
            });
        }

        if (!Schema::hasColumn('idempotency_keys', 'error_message')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->text('error_message')->nullable()->after('message_id');
            });
        }

        if (!Schema::hasColumn('idempotency_keys', 'processed_at')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->timestamp('processed_at')->nullable()->after('error_message');
            });
        }

        $supportingIndex = $this->findIndexByColumns('idempotency_keys', ['user_id', 'endpoint', 'status'], false);
        if ($supportingIndex === null && Schema::hasColumn('idempotency_keys', 'status')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->index(['user_id', 'endpoint', 'status'], 'idempotency_keys_user_endpoint_status_index');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            return;
        }

        $supportingIndex = $this->findIndexByColumns('idempotency_keys', ['user_id', 'endpoint', 'status'], false);
        if ($supportingIndex !== null) {
            Schema::table('idempotency_keys', function (Blueprint $table) use ($supportingIndex) {
                $table->dropIndex($supportingIndex);
            });
        }

        if (Schema::hasColumn('idempotency_keys', 'processed_at')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->dropColumn('processed_at');
            });
        }

        if (Schema::hasColumn('idempotency_keys', 'error_message')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->dropColumn('error_message');
            });
        }

        if (Schema::hasColumn('idempotency_keys', 'message_id')) {
            $msgIdIndex = $this->findIndexByColumns('idempotency_keys', ['message_id'], false);
            if ($msgIdIndex !== null) {
                Schema::table('idempotency_keys', function (Blueprint $table) use ($msgIdIndex) {
                    $table->dropIndex($msgIdIndex);
                });
            }
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->dropColumn('message_id');
            });
        }

        if (Schema::hasColumn('idempotency_keys', 'status')) {
            Schema::table('idempotency_keys', function (Blueprint $table) {
                $table->dropColumn('status');
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
