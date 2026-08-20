<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->json('playbook')->nullable()->after('monthly_token_budget')
                ->comment('per-tenant persona and behavior overrides (JSON)');
            $table->unsignedInteger('max_tokens_per_turn')->nullable()->after('playbook')
                ->comment('per-turn token ceiling for the agent loop (null = use default 800)');
        });
    }

    public function down(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->dropColumn(['playbook', 'max_tokens_per_turn']);
        });
    }
};
