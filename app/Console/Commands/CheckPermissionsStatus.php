<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class CheckPermissionsStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of permission translations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('');
        $this->info('========================================');
        $this->info('  PERMISSION SYSTEM STATUS CHECK');
        $this->info('========================================');
        $this->info('');

        // Get all permissions
        $permissions = DB::table('api_permissions')
            ->select('id', 'name', 'name_ar', 'name_en')
            ->orderBy('name')
            ->get();

        $withTranslations = $permissions->filter(function($p) {
            return !empty($p->name_ar) && !empty($p->name_en);
        });

        $withoutTranslations = $permissions->filter(function($p) {
            return empty($p->name_ar) || empty($p->name_en);
        });

        // Summary
        $this->info('📊 SUMMARY:');
        $this->info('───────────────────────────────────────');
        $this->line('Total Permissions: ' . $permissions->count());
        $this->line('✅ With Translations: ' . $withTranslations->count());
        $this->line('❌ Without Translations: ' . $withoutTranslations->count());
        $this->info('');

        // Show permissions with translations
        if ($withTranslations->count() > 0) {
            $this->info('✅ PERMISSIONS WITH TRANSLATIONS:');
            $this->info('───────────────────────────────────────');

            $withTranslations->each(function($perm) {
                $this->line('  • ' . $perm->name);
                $this->line('    EN: ' . $perm->name_en);
                $this->line('    AR: ' . $perm->name_ar);
            });
            $this->info('');
        }

        // Show permissions without translations
        if ($withoutTranslations->count() > 0) {
            $this->warn('❌ PERMISSIONS WITHOUT TRANSLATIONS:');
            $this->info('───────────────────────────────────────');
            $this->line('(These are typically internal/system permissions)');
            $this->info('');

            $withoutTranslations->each(function($perm) {
                $this->line('  • ' . $perm->name);
            });
            $this->info('');
        }

        // Check config
        $configPermissions = config('rbac.permissions', []);
        $this->info('📋 CONFIG CHECK:');
        $this->info('───────────────────────────────────────');
        $this->line('Permissions in config/rbac.php: ' . count($configPermissions));
        $this->info('');

        $this->info('========================================');
        $this->info('✅ CHECK COMPLETE');
        $this->info('========================================');
        $this->info('');

        return Command::SUCCESS;
    }
}
