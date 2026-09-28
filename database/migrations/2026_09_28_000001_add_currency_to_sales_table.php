<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            $table->char('currency', 3)->nullable()->after('sale_price');
            $table->index(['user_id', 'status', 'sale_date']);
        });
        Schema::table('rm_rentals', function (Blueprint $table): void {
            $table->index(['user_id', 'status', 'move_in_date', 'deleted_at'], 'rm_rentals_report_scope_date_index');
        });
        Schema::table('rm_payments', function (Blueprint $table): void {
            $table->index(['user_id', 'payment_type', 'payment_date', 'deleted_at', 'rental_id'], 'rm_payments_report_scope_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table): void {
            if ($this->indexExists('sales', 'sales_user_id_status_sale_date_index')) $table->dropIndex('sales_user_id_status_sale_date_index');
            $table->dropColumn('currency');
        });
        Schema::table('rm_rentals', function (Blueprint $table): void {
            if ($this->indexExists('rm_rentals', 'rm_rentals_report_scope_date_index')) $table->dropIndex('rm_rentals_report_scope_date_index');
        });
        Schema::table('rm_payments', function (Blueprint $table): void {
            if ($this->indexExists('rm_payments', 'rm_payments_report_scope_date_index')) $table->dropIndex('rm_payments_report_scope_date_index');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select('SHOW INDEX FROM `' . $table . '`'))
            ->contains(static fn (object $row): bool => (string) $row->Key_name === $index);
    }
};
