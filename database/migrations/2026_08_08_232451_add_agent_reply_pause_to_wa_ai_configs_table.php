<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->string('agent_reply_pause', 20)->default('48h')->after('monthly_token_budget')
                ->comment('How long to pause the bot after a human agent reply: off|24h|48h|indefinite');
        });
    }

    public function down(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->dropColumn('agent_reply_pause');
        });
    }
};
