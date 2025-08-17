<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('api_customers', function (Blueprint $t) {
            if (!Schema::hasColumn('api_customers', 'type_id')) {
                $t->unsignedBigInteger('type_id')->nullable()->after('procedure_id');
                $t->foreign('type_id', 'api_customers_type_id_fk')
                  ->references('id')->on('users_api_customers_types')->nullOnDelete();
            }
            if (!Schema::hasColumn('api_customers', 'priority_id')) {
                $t->unsignedBigInteger('priority_id')->nullable()->after('type_id');
                $t->foreign('priority_id', 'api_customers_priority_id_fk')
                  ->references('id')->on('users_api_customers_priorities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_customers', function (Blueprint $t) {
            if (Schema::hasColumn('api_customers', 'priority_id')) {
                $t->dropForeign('api_customers_priority_id_fk');
                $t->dropColumn('priority_id');
            }
            if (Schema::hasColumn('api_customers', 'type_id')) {
                $t->dropForeign('api_customers_type_id_fk');
                $t->dropColumn('type_id');
            }
        });
    }
};

