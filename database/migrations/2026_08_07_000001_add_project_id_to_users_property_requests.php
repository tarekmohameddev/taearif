<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_property_requests')) {
            return;
        }

        Schema::table('users_property_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('users_property_requests', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('initial_property_id');
                $table->index('project_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('user_projects')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users_property_requests')) {
            return;
        }

        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropIndex(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }
};
