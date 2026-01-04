<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_addons_audit')) {
            return;
        }

        // Make whatsapp_addon_id nullable to allow number audits
        if (Schema::hasColumn('whatsapp_addons_audit', 'whatsapp_addon_id')) {
            Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
                // Drop existing FK if present
                try {
                    $table->dropForeign(['whatsapp_addon_id']);
                } catch (\Throwable $e) {
                    // ignore if not present
                }
            });

            // Use raw SQL to avoid requiring doctrine/dbal
            try {
                DB::statement('ALTER TABLE whatsapp_addons_audit MODIFY whatsapp_addon_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // ignore if already nullable
            }

            Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
                // Recreate FK (nullable)
                $table->foreign('whatsapp_addon_id')->references('id')->on('whatsapp_addons')->nullOnDelete();
            });
        }

        Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_addons_audit', 'whatsapp_number_id')) {
                $table->foreignId('whatsapp_number_id')->nullable()->after('whatsapp_addon_id')->constrained('whatsapp_users');
            }

            if (!Schema::hasColumn('whatsapp_addons_audit', 'entity_type')) {
                $table->enum('entity_type', ['addon', 'number'])->default('addon')->after('whatsapp_number_id');
            }

            if (!Schema::hasColumn('whatsapp_addons_audit', 'note')) {
                $table->text('note')->nullable()->after('new_status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('whatsapp_addons_audit')) {
            return;
        }

        Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_addons_audit', 'whatsapp_number_id')) {
                try {
                    $table->dropForeign(['whatsapp_number_id']);
                } catch (\Throwable $e) {
                }
                $table->dropColumn('whatsapp_number_id');
            }

            if (Schema::hasColumn('whatsapp_addons_audit', 'entity_type')) {
                $table->dropColumn('entity_type');
            }

            if (Schema::hasColumn('whatsapp_addons_audit', 'note')) {
                $table->dropColumn('note');
            }
        });

        // Attempt to restore whatsapp_addon_id to not nullable
        if (Schema::hasColumn('whatsapp_addons_audit', 'whatsapp_addon_id')) {
            Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
                try {
                    $table->dropForeign(['whatsapp_addon_id']);
                } catch (\Throwable $e) {
                }
            });

            try {
                DB::statement('ALTER TABLE whatsapp_addons_audit MODIFY whatsapp_addon_id BIGINT UNSIGNED NOT NULL');
            } catch (\Throwable $e) {
                // leave as nullable if altering fails
            }

            Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
                $table->foreign('whatsapp_addon_id')->references('id')->on('whatsapp_addons')->onDelete('cascade');
            });
        }
    }
};

