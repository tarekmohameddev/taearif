<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\User\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductionFixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Log::info('Starting production fix for users with missing language data');
        
        try {
            // Get default language template
            $deLang = Language::where('user_id', 0)->first();
            if (!$deLang) {
                Log::error('Default language template not found!');
                $this->command->error('Default language template not found!');
                return;
            }

            // Find users without languages (chunk to avoid memory issues)
            $usersWithoutLanguages = User::whereDoesntHave('languages')
                ->where('status', 1) // Only active users
                ->chunk(100, function ($users) use ($deLang) {
                    foreach ($users as $user) {
                        try {
                            DB::transaction(function () use ($user, $deLang) {
                                // Check if user already has language (double-check)
                                if ($user->languages()->count() > 0) {
                                    return;
                                }
                                
                                // Create default language for user
                                $lang = new Language;
                                $lang->name = $deLang->name;
                                $lang->code = $deLang->code;
                                $lang->is_default = 1;
                                $lang->rtl = $deLang->rtl;
                                $lang->user_id = $user->id;
                                $lang->keywords = $deLang->keywords;
                                $lang->save();
                                
                                Log::info("Fixed user language: {$user->username} (ID: {$user->id})");
                            });
                        } catch (\Exception $e) {
                            Log::error("Failed to fix user {$user->username}: " . $e->getMessage());
                        }
                    }
                });

            Log::info('Production fix completed successfully');
            
        } catch (\Exception $e) {
            Log::error('Production fix failed: ' . $e->getMessage());
            $this->command->error('Production fix failed: ' . $e->getMessage());
        }
    }
}
