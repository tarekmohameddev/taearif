<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customers','type_id')) {
                $table->unsignedBigInteger('type_id')->nullable()->after('procedure_id');
            }
            if (!Schema::hasColumn('api_customers','priority_id')) {
                $table->unsignedBigInteger('priority_id')->nullable()->after('type_id');
            }

            // Helpful indexes for search
            $table->index(['user_id','type_id']);
            $table->index(['user_id','priority_id']);

            // FKs (nullable, user-scoped logically at app layer)
            $table->foreign('type_id')
                ->references('id')->on('users_api_customers_types')
                ->nullOnDelete();

            $table->foreign('priority_id')
                ->references('id')->on('users_api_customers_priorities')
                ->nullOnDelete();

            // Drop legacy columns if they exist
            if (Schema::hasColumn('api_customers','customer_type')) {
                $table->dropColumn('customer_type');
            }
            if (Schema::hasColumn('api_customers','priority')) {
                $table->dropColumn('priority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_customers', function (Blueprint $table) {
            // re-add legacy columns if you need a rollback
            $table->string('customer_type')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();

            $table->dropForeign(['type_id']);
            $table->dropForeign(['priority_id']);
            $table->dropColumn(['type_id','priority_id']);
        });
    }
};

