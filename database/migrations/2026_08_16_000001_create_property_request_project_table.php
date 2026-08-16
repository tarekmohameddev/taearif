<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users_property_requests') || ! Schema::hasTable('user_projects')) {
            return;
        }

        if (! Schema::hasTable('property_request_project')) {
            Schema::create('property_request_project', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_request_id');
                $table->unsignedBigInteger('project_id');
                $table->unique(['property_request_id', 'project_id']);
                $table->foreign('property_request_id')
                    ->references('id')
                    ->on('users_property_requests')
                    ->cascadeOnDelete();
                $table->foreign('project_id')
                    ->references('id')
                    ->on('user_projects')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('users_property_requests', 'project_id')) {
            DB::table('users_property_requests')
                ->whereNotNull('project_id')
                ->orderBy('id')
                ->select(['id', 'project_id'])
                ->chunkById(500, function ($rows): void {
                    DB::table('property_request_project')->insertOrIgnore(
                        $rows->map(fn ($row) => [
                            'property_request_id' => $row->id,
                            'project_id' => $row->project_id,
                        ])->all()
                    );
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_request_project');
    }
};
