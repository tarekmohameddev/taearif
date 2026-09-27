<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        'rm_contracts' => ['rms_dashboard_contracts_user_status_end_id' => ['user_id', 'status', 'end_date', 'id']],
        'rm_payment_installments' => ['rms_dashboard_installments_user_due_id_status' => ['user_id', 'due_date', 'id', 'status']],
        'rm_maintenance_tickets' => ['rms_dashboard_maintenance_user_status_created_id' => ['user_id', 'status', 'created_at', 'id']],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (Schema::hasTable($table) && ! $this->indexExists($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (Schema::hasTable($table) && $this->indexExists($table, $name)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
                }
            }
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM {$table}"))->contains(
            static fn (object $row): bool => $row->Key_name === $index
        );
    }
};
