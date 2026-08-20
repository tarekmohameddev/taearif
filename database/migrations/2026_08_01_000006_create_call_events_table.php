<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('call_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('call_log_id');
            $table->foreign('call_log_id')->references('id')->on('call_logs')->onDelete('cascade');
            $table->string('event_name');
            $table->json('payload');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['call_log_id', 'created_at']);
            $table->index('created_at'); // for prune-events retention cleanup
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_events');
    }
};
