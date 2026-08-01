<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_trunks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name');
            $table->string('type');         // yeastar_gsm | stc_sip
            $table->string('ownership');    // customer_owned | company_hosted
            $table->string('registration_mode')->default('register'); // register | ip_auth
            $table->string('asterisk_endpoint_prefix');
            $table->string('status')->default('pending'); // pending | registered | unregistered | error
            $table->timestamp('status_checked_at')->nullable();
            // Sensitive credentials stored encrypted; never returned on list responses
            $table->text('credentials_encrypted')->nullable();
            // Non-secret options (STC host/port etc.)
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_trunks');
    }
};
