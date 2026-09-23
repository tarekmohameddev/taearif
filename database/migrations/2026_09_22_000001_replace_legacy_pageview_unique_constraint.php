<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pageview_analytics', function (Blueprint $table) {
            // The canonical row identity is tenant + normalized path + date.
            // A slug can legitimately have separate localized paths on the same day.
            $table->dropUnique('unique_tenant_page_date');
            $table->index(
                ['tenant_id', 'page_type', 'page_slug', 'date_bucket'],
                'idx_pageviews_tenant_type_slug_date'
            );
        });
    }

    public function down(): void
    {
        Schema::table('pageview_analytics', function (Blueprint $table) {
            $table->dropIndex('idx_pageviews_tenant_type_slug_date');
            $table->unique(
                ['tenant_id', 'page_slug', 'dynamic_slug', 'date_bucket'],
                'unique_tenant_page_date'
            );
        });
    }
};
