<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_eval_runs', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id')
                ->comment('Tenant ID; null = platform-wide evaluation run');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('ai_eval_runs', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
