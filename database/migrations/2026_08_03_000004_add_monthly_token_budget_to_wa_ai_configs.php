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
            $table->unsignedInteger('monthly_token_budget')->default(0)->after('assistant_name')
                ->comment('0 = unlimited; >0 caps the tenant monthly AI spend');
        });
    }

    public function down(): void
    {
        Schema::table('wa_ai_configs', function (Blueprint $table) {
            $table->dropColumn('monthly_token_budget');
        });
    }
};
