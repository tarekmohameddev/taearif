<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('email_campaigns')) {
            return;
        }

        if (Schema::hasColumn('email_campaigns', 'template_id')) {
            Schema::table('email_campaigns', function (Blueprint $table) {
                try {
                    $table->dropForeign(['template_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key doesn't exist.
                }
            });
        }

        if (Schema::hasTable('email_campaign_templates') && Schema::hasColumn('email_campaigns', 'template_id')) {
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->foreign('template_id')->references('id')->on('email_campaign_templates')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('email_campaigns')) {
            return;
        }

        if (Schema::hasColumn('email_campaigns', 'template_id')) {
            Schema::table('email_campaigns', function (Blueprint $table) {
                try {
                    $table->dropForeign(['template_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key doesn't exist.
                }
            });
        }

        if (Schema::hasTable('email_templates') && Schema::hasColumn('email_campaigns', 'template_id')) {
            Schema::table('email_campaigns', function (Blueprint $table) {
                $table->foreign('template_id')->references('id')->on('email_templates')->nullOnDelete();
            });
        }
    }
};
