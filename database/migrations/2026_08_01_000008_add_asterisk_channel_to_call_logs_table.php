<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            // Stores the Asterisk channel name (e.g. PJSIP/agent_42_7-00000001)
            // captured from the OriginateResponse AMI event so that targeted
            // per-channel hangup is possible instead of mass-hangup.
            $table->string('asterisk_channel', 100)->nullable()->after('sim_line_id');
        });
    }

    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn('asterisk_channel');
        });
    }
};
