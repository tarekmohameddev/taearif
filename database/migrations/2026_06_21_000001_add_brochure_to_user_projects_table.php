<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_projects', function (Blueprint $table) {
            $table->text('brochure')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('user_projects', function (Blueprint $table) {
            $table->dropColumn('brochure');
        });
    }
};
