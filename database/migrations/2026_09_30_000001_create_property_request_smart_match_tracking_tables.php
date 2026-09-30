<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_request_smart_match_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_request_id');
            $table->unsignedInteger('match_count')->default(0);
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'property_request_id', 'id'], 'pr_smr_latest_idx');
        });

        Schema::create('property_request_smart_match_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('property_request_id');
            $table->unsignedBigInteger('account_user_id');
            $table->unsignedBigInteger('last_read_match_run_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'property_request_id', 'account_user_id'], 'pr_smr_read_unique');
            $table->index(['tenant_id', 'property_request_id'], 'pr_smr_read_request_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_request_smart_match_reads');
        Schema::dropIfExists('property_request_smart_match_runs');
    }
};
