<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\BasicSetting;
use App\Models\User\Menu;
use App\Models\User\HomePageText;
use App\Models\User\UserPermission;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for users with missing required data and fix them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting health check...');
        
        $issues = [];
        
        // Check for users without languages
        $usersWithoutLanguages = User::whereDoesntHave('languages')
            ->where('status', 1)
            ->count();
            
        if ($usersWithoutLanguages > 0) {
            $issues[] = "{$usersWithoutLanguages} users without language data";
        }
        
        // Check for users without basic settings
        $usersWithoutBasicSettings = User::whereDoesntHave('basic_setting')
            ->where('status', 1)
            ->count();
            
        if ($usersWithoutBasicSettings > 0) {
            $issues[] = "{$usersWithoutBasicSettings} users without basic settings";
        }
        
        // Check for users without menus
        $usersWithoutMenus = User::whereDoesntHave('menus')
            ->where('status', 1)
            ->count();
            
        if ($usersWithoutMenus > 0) {
            $issues[] = "{$usersWithoutMenus} users without menu data";
        }
        
        // Check for users without permissions
        $usersWithoutPermissions = User::whereDoesntHave('permissions')
            ->where('status', 1)
            ->count();
            
        if ($usersWithoutPermissions > 0) {
            $issues[] = "{$usersWithoutPermissions} users without permissions";
        }
        
        if (empty($issues)) {
            $this->info('✅ All users have required data. System is healthy!');
            return 0;
        }
        
        $this->warn('⚠️  Found issues:');
        foreach ($issues as $issue) {
            $this->warn("  - {$issue}");
        }
        
        if ($this->confirm('Do you want to fix these issues automatically?')) {
            $this->fixIssues();
        }
        
        return 0;
    }
    
    private function fixIssues()
    {
        $this->info('Fixing issues...');
        
        // Get default language template
        $deLang = Language::where('user_id', 0)->first();
        if (!$deLang) {
            $this->error('Default language template not found!');
            return;
        }
        
        // Fix users without languages
        $usersWithoutLanguages = User::whereDoesntHave('languages')
            ->where('status', 1)
            ->get();
            
        foreach ($usersWithoutLanguages as $user) {
            try {
                $lang = new Language;
                $lang->name = $deLang->name;
                $lang->code = $deLang->code;
                $lang->is_default = 1;
                $lang->rtl = $deLang->rtl;
                $lang->user_id = $user->id;
                $lang->keywords = $deLang->keywords;
                $lang->save();
                
                $this->info("✅ Fixed language for user: {$user->username}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to fix user {$user->username}: " . $e->getMessage());
            }
        }
        
        $this->info('Health check completed!');
    }
}
