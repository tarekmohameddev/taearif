<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id')
                  ->nullable()
                  ->after('user_id');
            $table->foreign('project_id')
                  ->references('id')
                  ->on('user_projects')
                  ->onDelete('set null');
            $table->index('project_id', 'idx_buildings_project_id');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropIndex('idx_buildings_project_id');
            $table->dropColumn('project_id');
        });
    }
};
