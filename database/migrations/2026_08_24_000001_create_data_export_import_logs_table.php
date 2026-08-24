<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail for tenant data export/import operations performed from the
     * admin panel: who ran it, which tenant was affected, and the outcome.
     */
    public function up(): void
    {
        Schema::create('data_export_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->nullable();  // admin who ran it
            $table->unsignedBigInteger('user_id')->nullable();   // affected tenant
            $table->string('affected_username')->nullable();     // snapshot, survives tenant deletion
            $table->string('operation', 20);                     // export | import
            $table->string('status', 20);                        // success | partial | failed
            $table->string('file_name')->nullable();
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->boolean('update_existing')->default(false);  // import option used
            $table->text('message')->nullable();                 // error / note summary
            $table->json('metadata')->nullable();                // per-sheet breakdown, etc.
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            $table->index(['operation', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('admin_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_export_import_logs');
    }
};
