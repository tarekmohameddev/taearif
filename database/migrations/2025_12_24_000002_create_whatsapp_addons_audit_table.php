<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_addons_audit')) {
            return;
        }

        Schema::create('whatsapp_addons_audit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_addon_id')->constrained('whatsapp_addons');
            $table->foreignId('changed_by')->nullable()->constrained('admins');
            $table->enum('old_status', ['pending', 'approved', 'rejected'])->nullable();
            $table->enum('new_status', ['pending', 'approved', 'rejected']);
            $table->timestamp('changed_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_addons_audit');
    }
};

