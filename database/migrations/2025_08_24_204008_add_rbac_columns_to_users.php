<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('rbac_version')->default(0)->after('remember_token');
            $table->timestamp('rbac_seeded_at')->nullable()->after('rbac_version');
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rbac_version','rbac_seeded_at']);
        });
    }
};
