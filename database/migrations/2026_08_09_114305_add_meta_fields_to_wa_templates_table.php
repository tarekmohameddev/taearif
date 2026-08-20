<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_templates', function (Blueprint $table) {
            $table->string('meta_template_id', 50)->nullable()->after('user_id');
            $table->string('status', 20)->nullable()->after('language');
            $table->json('components')->nullable()->after('variables');
            $table->string('namespace', 100)->nullable()->after('components');
            $table->timestamp('synced_at')->nullable()->after('namespace');

            $table->index(['user_id', 'status'], 'wa_templates_user_id_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('wa_templates', function (Blueprint $table) {
            $table->dropIndex('wa_templates_user_id_status_index');
            $table->dropColumn(['meta_template_id', 'status', 'components', 'namespace', 'synced_at']);
        });
    }
};
