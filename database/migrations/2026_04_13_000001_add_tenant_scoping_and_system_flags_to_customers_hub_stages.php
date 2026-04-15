<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        Schema::table('customers_hub_stages', function (Blueprint $table) {
            if (! Schema::hasColumn('customers_hub_stages', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id');
                $table->index('user_id');
            }
            if (! Schema::hasColumn('customers_hub_stages', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('user_id');
                $table->index('is_system');
            }
        });

        // Mark the required default stages as system stages.
        DB::table('customers_hub_stages')
            ->whereIn('stage_id', ['new_lead', 'deal_completed', 'deal_rejected'])
            ->update([
                'is_system' => true,
                'user_id' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        Schema::table('customers_hub_stages', function (Blueprint $table) {
            if (Schema::hasColumn('customers_hub_stages', 'is_system')) {
                $table->dropIndex(['is_system']);
                $table->dropColumn('is_system');
            }
            if (Schema::hasColumn('customers_hub_stages', 'user_id')) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};

