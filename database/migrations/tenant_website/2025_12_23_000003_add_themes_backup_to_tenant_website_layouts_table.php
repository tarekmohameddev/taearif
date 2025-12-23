<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('tenant_website_layouts')) {
            Schema::table('tenant_website_layouts', function (Blueprint $table) {
                if (!Schema::hasColumn('tenant_website_layouts', 'themes_backup')) {
                    $table->json('themes_backup')->nullable()->after('published_data');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('tenant_website_layouts')) {
            Schema::table('tenant_website_layouts', function (Blueprint $table) {
                if (Schema::hasColumn('tenant_website_layouts', 'themes_backup')) {
                    $table->dropColumn('themes_backup');
                }
            });
        }
    }
};

