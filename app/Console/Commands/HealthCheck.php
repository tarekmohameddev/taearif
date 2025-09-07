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
    protected $signature = 'health:check {--auto : Run automatically without prompts}';

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
        
        if ($this->option('auto') || $this->confirm('Do you want to fix these issues automatically?')) {
            $this->fixIssues();
        } else {
            $this->info('Health check completed without fixing issues.');
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
        
        $fixedCount = 0;
        
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
                $fixedCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to fix language for user {$user->username}: " . $e->getMessage());
            }
        }
        
        // Fix users without basic settings
        $usersWithoutBasicSettings = User::whereDoesntHave('basic_setting')
            ->where('status', 1)
            ->get();
            
        foreach ($usersWithoutBasicSettings as $user) {
            try {
                $this->createBasicSetting($user);
                $this->info("✅ Fixed basic settings for user: {$user->username}");
                $fixedCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to fix basic settings for user {$user->username}: " . $e->getMessage());
            }
        }
        
        // Fix users without menus
        $usersWithoutMenus = User::whereDoesntHave('menus')
            ->where('status', 1)
            ->get();
            
        foreach ($usersWithoutMenus as $user) {
            try {
                $this->createMenu($user);
                $this->info("✅ Fixed menu for user: {$user->username}");
                $fixedCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to fix menu for user {$user->username}: " . $e->getMessage());
            }
        }
        
        // Fix users without permissions
        $usersWithoutPermissions = User::whereDoesntHave('permissions')
            ->where('status', 1)
            ->get();
            
        foreach ($usersWithoutPermissions as $user) {
            try {
                $this->createPermission($user);
                $this->info("✅ Fixed permissions for user: {$user->username}");
                $fixedCount++;
            } catch (\Exception $e) {
                $this->error("❌ Failed to fix permissions for user {$user->username}: " . $e->getMessage());
            }
        }
        
        $this->info("Health check completed! Fixed {$fixedCount} issues.");
    }
    
    private function createBasicSetting($user)
    {
        $basicSettingsJson = '{
            "favicon": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
            "breadcrumb": "https://codecanyon8.kreativdev.com/estaty/assets/img/hero/static/6574372e0ad77.jpg",
            "logo": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
            "preloader": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
            "base_color": "0003FF",
            "secondary_color": "00F5E5",
            "theme": "home13",
            "from_name": null,
            "is_quote": "1",
            "qr_image": "6727bead51be1.png",
            "qr_color": "000000",
            "qr_size": "248",
            "qr_style": "square",
            "qr_eye_style": "square",
            "qr_margin": "0",
            "qr_text": null,
            "qr_text_color": "000000",
            "qr_text_size": "15",
            "qr_text_x": "50",
            "qr_text_y": "50",
            "qr_inserted_image": null,
            "qr_inserted_image_size": "20",
            "qr_inserted_image_x": "50",
            "qr_inserted_image_y": "50",
            "qr_type": "default",
            "qr_url": "https://taearif.com/rangs",
            "whatsapp_status": "0",
            "whatsapp_number": null,
            "whatsapp_header_title": null,
            "whatsapp_popup_status": "0",
            "whatsapp_popup_message": null,
            "disqus_status": "0",
            "disqus_short_name": null,
            "analytics_status": "0",
            "measurement_id": null,
            "pixel_status": "0",
            "pixel_id": null,
            "tawkto_status": "0",
            "tawkto_direct_chat_link": null,
            "custom_css": null,
            "website_title": "User Website",
            "base_currency_symbol": "$",
            "base_currency_symbol_position": "left",
            "base_currency_text": "USD",
            "base_currency_rate": null,
            "base_currency_text_position": null,
            "is_recaptcha": "0",
            "google_recaptcha_site_key": null,
            "google_recaptcha_secret_key": null,
            "adsense_publisher_id": null,
            "timezone": "1",
            "features_section_image": null,
            "cv": null,
            "cv_original": null,
            "email_verification_status": "1",
            "cookie_alert_status": "0",
            "cookie_alert_text": null,
            "cookie_alert_button_text": null,
            "property_country_status": "1",
            "property_state_status": "1",
            "short_description": "User website for testing and development.",
            "industry_type": "Real Estate Company"
        }';

        $basicSettingsArray = json_decode($basicSettingsJson, true);
        $basicSettingsArray['email'] = $user->email;
        $basicSettingsArray['user_id'] = $user->id;

        \App\Models\User\BasicSetting::create($basicSettingsArray);
    }
    
    private function createMenu($user)
    {
        $lang = $user->languages()->where('is_default', 1)->first();
        if (!$lang) {
            throw new \Exception('User has no default language');
        }
        
        $menus = '[
            {"text":"Home","href":"","icon":"empty","target":"_self","title":"","type":"home"},
            {"text":"About","href":"","icon":"empty","target":"_self","title":"","type":"custom","children":[
                {"text":"Team","href":"","icon":"empty","target":"_self","title":"","type":"team"},
                {"text":"Career","href":"","icon":"empty","target":"_self","title":"","type":"career"},
                {"text":"FAQ","href":"","icon":"empty","target":"_self","title":"","type":"faq"}
            ]},
            {"text":"Services","href":"","icon":"empty","target":"_self","title":"","type":"services"},
            {"text":"Blog","href":"","icon":"empty","target":"_self","title":"","type":"blog"},
            {"text":"Contact","href":"","icon":"empty","target":"_self","title":"","type":"contact"}
        ]';

        $umenu = new \App\Models\User\Menu();
        $umenu->language_id = $lang->id;
        $umenu->user_id = $user->id;
        $umenu->menus = $menus;
        $umenu->save();
    }
    
    private function createPermission($user)
    {
        $currentMembership = \App\Http\Helpers\UserPermissionHelper::userPackage($user->id);
        if ($currentMembership) {
            \App\Models\User\UserPermission::create([
                'user_id' => $user->id,
                'package_id' => $currentMembership->package_id,
            ]);
        } else {
            // If no active membership, create permission for free package
            $freePackage = \App\Models\Package::find(16);
            if ($freePackage) {
                \App\Models\User\UserPermission::create([
                    'user_id' => $user->id,
                    'package_id' => $freePackage->id,
                ]);
            }
        }
    }
}
