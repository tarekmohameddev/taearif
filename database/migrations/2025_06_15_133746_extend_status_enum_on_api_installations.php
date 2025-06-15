<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("
            ALTER TABLE api_installations
            MODIFY status ENUM(
                'installed',
                'uninstalled',
                'trialing',
                'pending_payment',
                'expired'
            ) NOT NULL DEFAULT 'installed'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE api_installations
            MODIFY status ENUM('installed','uninstalled')
                NOT NULL DEFAULT 'installed'
        ");
    }
};
