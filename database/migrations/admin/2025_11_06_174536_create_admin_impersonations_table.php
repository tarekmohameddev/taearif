<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('admin_impersonations', function (Blueprint $table) {
            $table->id();

            // Who and What
            $table->unsignedBigInteger('admin_id')->comment('Admin who initiated impersonation');
            $table->unsignedBigInteger('user_id')->comment('User being impersonated (tenant)');
            $table->unsignedBigInteger('token_id')->nullable()->comment('Sanctum personal access token ID');

            // Timestamps & Duration
            $table->timestamp('started_at')->useCurrent()->comment('When impersonation started');
            $table->timestamp('ended_at')->nullable()->comment('When impersonation ended (NULL = active)');
            $table->integer('duration_seconds')->nullable()->comment('Session duration in seconds');

            // Tracking & Audit
            $table->string('ip_address', 45)->nullable()->comment('IP address (IPv4/IPv6)');
            $table->text('user_agent')->nullable()->comment('Browser user agent');
            $table->string('reason')->nullable()->comment('Why admin is impersonating (optional)');
            $table->integer('actions_count')->default(0)->comment('Number of API calls made during session');

            // Status
            $table->enum('status', ['active', 'ended', 'expired', 'revoked'])
                ->default('active')
                ->comment('Session status');

            // Laravel timestamps
            $table->timestamps();

            // Indexes for performance
            $table->index('admin_id', 'idx_admin_id');
            $table->index('user_id', 'idx_user_id');
            $table->index('status', 'idx_status');
            $table->index('started_at', 'idx_started_at');
            $table->index(['admin_id', 'status'], 'idx_admin_status');
            $table->index(['user_id', 'status'], 'idx_user_status');

            // Foreign keys
            $table->foreign('admin_id')
                ->references('id')
                ->on('admins')
                ->onDelete('cascade')
                ->comment('Admin who impersonated');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade')
                ->comment('User who was impersonated');

            $table->foreign('token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->onDelete('set null')
                ->comment('Sanctum token used for impersonation');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admin_impersonations');
    }
};
