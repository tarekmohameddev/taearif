<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->string('goal', 30)->default('support')->after('custom_instructions');
            $table->string('autonomy_level', 20)->default('off')->after('goal');
            $table->unsignedSmallInteger('reply_length_target')->default(200)->after('autonomy_level');
            $table->unsignedTinyInteger('confidence_threshold')->default(70)->after('reply_length_target');
            $table->unsignedTinyInteger('groundedness_threshold')->default(80)->after('confidence_threshold');
            $table->json('escalation_rules')->nullable()->after('groundedness_threshold');
            $table->boolean('disclose_as_assistant')->default(true)->after('escalation_rules');
            $table->string('assistant_name', 100)->nullable()->after('disclose_as_assistant');
        });
    }

    public function down(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->dropColumn([
                'goal',
                'autonomy_level',
                'reply_length_target',
                'confidence_threshold',
                'groundedness_threshold',
                'escalation_rules',
                'disclose_as_assistant',
                'assistant_name',
            ]);
        });
    }
};
