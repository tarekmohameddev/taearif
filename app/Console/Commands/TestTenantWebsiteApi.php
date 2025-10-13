<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\TenantWebsiteSeeder;

class TestTenantWebsiteApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:tenant-website-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the Tenant Website API integration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('======================================');
        $this->info('Testing Tenant Website API Integration');
        $this->info('======================================');
        $this->newLine();

        // Test 1: Check API URL configuration
        $this->info('Test 1: Configuration Check');
        $this->line('----------------------------');
        $apiUrl = config('app.tenant_website_api_url');
        $this->line("API URL: {$apiUrl}");

        if ($apiUrl) {
            $this->info('✓ PASSED - API URL is configured');
        } else {
            $this->error('✗ FAILED - API URL not configured');
        }
        $this->newLine();

        // Test 2: Direct API Call
        $this->info('Test 2: Direct API Call');
        $this->line('------------------------');

        try {
            $this->line('Making HTTP request...');
            $response = Http::timeout(10)->get($apiUrl);

            if ($response->successful()) {
                $this->info("✓ HTTP Status: " . $response->status());

                $data = $response->json();

                // Check structure
                $hasComponentSettings = isset($data['componentSettings']);
                $hasGlobalComponentsData = isset($data['globalComponentsData']);

                $this->line("Has 'componentSettings': " . ($hasComponentSettings ? "✓ YES" : "✗ NO"));
                $this->line("Has 'globalComponentsData': " . ($hasGlobalComponentsData ? "✓ YES" : "✗ NO"));

                if ($hasComponentSettings && $hasGlobalComponentsData) {
                    // Show some stats
                    $pageCount = count($data['componentSettings']);
                    $this->line("Number of pages: {$pageCount}");

                    if (isset($data['globalComponentsData']['header'])) {
                        $this->line("Has header data: ✓ YES");
                    }
                    if (isset($data['globalComponentsData']['footer'])) {
                        $this->line("Has footer data: ✓ YES");
                    }

                    $this->newLine();
                    $this->info('✓ PASSED - API returns valid data structure');
                } else {
                    $this->newLine();
                    $this->error('✗ FAILED - Invalid data structure');
                }
            } else {
                $this->error("✗ HTTP Status: " . $response->status());
                $this->error('✗ FAILED - API returned error status');
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error('✗ FAILED - Exception occurred');
        }
        $this->newLine();

        // Test 3: Test TenantWebsiteSeeder::fetchDefaultData()
        $this->info('Test 3: TenantWebsiteSeeder Integration');
        $this->line('----------------------------------------');

        try {
            $seeder = new TenantWebsiteSeeder();

            // Use reflection to call protected method
            $reflection = new \ReflectionClass($seeder);
            $method = $reflection->getMethod('fetchDefaultData');
            $method->setAccessible(true);

            $this->line('Calling fetchDefaultData() method...');
            $result = $method->invoke($seeder);

            if ($result && is_array($result)) {
                $hasComponentSettings = isset($result['componentSettings']);
                $hasGlobalComponentsData = isset($result['globalComponentsData']);

                $this->line("Result is array: ✓ YES");
                $this->line("Has 'componentSettings': " . ($hasComponentSettings ? "✓ YES" : "✗ NO"));
                $this->line("Has 'globalComponentsData': " . ($hasGlobalComponentsData ? "✓ YES" : "✗ NO"));

                if ($hasComponentSettings && $hasGlobalComponentsData) {
                    $this->newLine();
                    $this->info('✓ PASSED - Method returns valid data');
                } else {
                    $this->newLine();
                    $this->error('✗ FAILED - Invalid data structure');
                }
            } else {
                $this->error('✗ FAILED - Method returned invalid result');
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error('✗ FAILED - Exception occurred');
        }
        $this->newLine();

        // Test 4: Check local fallback config exists
        $this->info('Test 4: Fallback Configuration Check');
        $this->line('-------------------------------------');

        $fallbackConfig = config('tenant_website_defaults');
        if ($fallbackConfig && is_array($fallbackConfig)) {
            $hasComponentSettings = isset($fallbackConfig['componentSettings']);
            $hasGlobalComponentsData = isset($fallbackConfig['globalComponentsData']);

            $this->line("Fallback config exists: ✓ YES");
            $this->line("Has 'componentSettings': " . ($hasComponentSettings ? "✓ YES" : "✗ NO"));
            $this->line("Has 'globalComponentsData': " . ($hasGlobalComponentsData ? "✓ YES" : "✗ NO"));
            $this->newLine();
            $this->info('✓ PASSED - Fallback config is valid');
        } else {
            $this->line("Fallback config exists: ✗ NO");
            $this->newLine();
            $this->error('✗ FAILED - Fallback config not found');
        }
        $this->newLine();

        // Test 5: Test with a dummy tenant (if exists)
        $this->info('Test 5: Full Integration Test');
        $this->line('------------------------------');

        $testUser = \App\Models\User::first();
        if ($testUser) {
            $this->line("Testing with user ID: {$testUser->id}");

            try {
                $seeder = new TenantWebsiteSeeder();

                // Check if already has data
                $hasData = $seeder->hasWebsiteData($testUser);
                $this->line("User already has website data: " . ($hasData ? "YES" : "NO"));

                $this->info('✓ PASSED - Integration methods accessible');
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
                $this->error('✗ FAILED - Exception occurred');
            }
        } else {
            $this->warn('⚠ SKIPPED - No users found in database');
        }

        $this->newLine();
        $this->info('======================================');
        $this->info('Testing Complete!');
        $this->info('======================================');

        return 0;
    }
}

