<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ApiTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Testing API user registration...');
        
        // Test registration endpoint - adjust this URL to match your setup
        $baseUrl = 'http://localhost'; // Change this to your actual domain
        $registerEndpoint = $baseUrl . '/api/register';
        
        // Generate test user data
        $userData = [
            'first_name' => 'API',
            'last_name' => 'Test',
            'email' => 'apitest_' . Str::random(8) . '@example.com',
            'username' => 'apitest_' . Str::random(6),
            'password' => '123123123',
            'password_confirmation' => '123123123',
            'recaptcha_token' => 'test_token', // You might need to handle this properly
        ];
        
        $this->command->info("Testing registration for: " . $userData['email']);
        $this->command->info("Endpoint: " . $registerEndpoint);
        
        try {
            $response = Http::timeout(30)->post($registerEndpoint, $userData);
            
            if ($response->successful()) {
                $responseData = $response->json();
                $this->command->info("✅ API Registration Success!");
                $this->command->info("   Email: " . $userData['email']);
                $this->command->info("   Username: " . $userData['username']);
                $this->command->info("   Password: 123123123");
                $this->command->info("   Token: " . substr($responseData['token'] ?? 'N/A', 0, 20) . '...');
                $this->command->info("   Membership expires: " . ($responseData['membership']['expire_date'] ?? 'N/A'));
                $this->command->info("   Package price: " . ($responseData['membership']['package_price'] ?? 'N/A'));
                $this->command->info("   Trial days: " . ($responseData['membership']['trial_days'] ?? 'N/A'));
            } else {
                $this->command->error("❌ API Registration Failed!");
                $this->command->error("   Status: " . $response->status());
                $this->command->error("   Response: " . $response->body());
            }
        } catch (\Exception $e) {
            $this->command->error("❌ Exception: " . $e->getMessage());
            $this->command->info("Make sure your Laravel server is running and the API endpoint is accessible.");
        }
        
        $this->command->info('API test completed!');
    }
}
