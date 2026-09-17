<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->cascadeOnDelete();
            $table->index(['user_id', 'status', 'expire_date'], 'wa_addons_tenant_status_expire_idx');
        });

        DB::table('whatsapp_addons')
            ->join('whatsapp_users', 'whatsapp_users.id', '=', 'whatsapp_addons.whatsapp_number_id')
            ->whereNull('whatsapp_addons.user_id')
            ->update(['whatsapp_addons.user_id' => DB::raw('whatsapp_users.user_id')]);

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE whatsapp_addons MODIFY whatsapp_number_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('whatsapp_addons', function (Blueprint $table) {
                $table->unsignedBigInteger('whatsapp_number_id')->nullable()->change();
            });
        }

        Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('action', 32)->nullable()->after('entity_type');
            $table->unsignedInteger('quantity')->nullable()->after('action');
            $table->unsignedInteger('old_quota')->nullable()->after('quantity');
            $table->unsignedInteger('new_quota')->nullable()->after('old_quota');
            $table->uuid('correlation_id')->nullable()->after('note');
            $table->string('ip_address', 45)->nullable()->after('correlation_id');
            $table->json('metadata')->nullable()->after('ip_address');
            $table->index(['tenant_id', 'changed_at'], 'wa_addon_audit_tenant_changed_idx');
        });

        $legacyGrants = DB::table('whatsapp_addons')
            ->whereNotNull('user_id')
            ->where('gateway_transaction_id', 'manual_admin_grant')
            ->where('status', 'approved')
            ->get();

        foreach ($legacyGrants as $grant) {
            $alreadyAudited = DB::table('whatsapp_addons_audit')
                ->where('whatsapp_addon_id', $grant->id)
                ->exists();

            if (!$alreadyAudited) {
                DB::table('whatsapp_addons_audit')->insert([
                    'tenant_id' => $grant->user_id,
                    'whatsapp_addon_id' => $grant->id,
                    'entity_type' => 'addon',
                    'action' => 'grant',
                    'quantity' => $grant->qty,
                    'old_status' => null,
                    'new_status' => 'approved',
                    'note' => 'Legacy manual grant backfilled during secure quota migration',
                    'correlation_id' => (string) \Illuminate\Support\Str::uuid(),
                    'metadata' => json_encode(['payment_ref' => $grant->payment_ref]),
                    'changed_at' => $grant->created_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('whatsapp_addons_audit', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex('wa_addon_audit_tenant_changed_idx');
            $table->dropColumn([
                'tenant_id', 'action', 'quantity', 'old_quota', 'new_quota',
                'correlation_id', 'ip_address', 'metadata',
            ]);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE whatsapp_addons MODIFY whatsapp_number_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex('wa_addons_tenant_status_expire_idx');
            $table->dropColumn('user_id');
        });
    }
};
