<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TestUserRegistrationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Starting test user registration...');
        
        // Test registration endpoint
        $baseUrl = 'http://localhost'; // Change this to your actual domain
        $registerEndpoint = $baseUrl . '/api/register';
        
        // Generate test users
        $testUsers = [];
        for ($i = 1; $i <= 1; $i++) {
            $testUsers[] = [
                'first_name' => 'Test',
                'last_name' => 'User' . $i,
                'email' => 'testuser' . $i . '_' . Str::random(8) . '@example.com',
                'username' => 'testuser' . $i . '_' . Str::random(6),
                'password' => '123123123',
                'password_confirmation' => '123123123',
                'recaptcha_token' => 'test_token', // You might need to handle this
            ];
        }
        
        $this->command->info('Generated ' . count($testUsers) . ' test users');
        
        foreach ($testUsers as $index => $userData) {
            $this->command->info("Registering user " . ($index + 1) . ": " . $userData['email']);
            
            try {
                $response = Http::timeout(30)->post($registerEndpoint, $userData);
                
                if ($response->successful()) {
                    $responseData = $response->json();
                    $this->command->info("✅ Success: " . $userData['email']);
                    $this->command->info("   Token: " . substr($responseData['token'] ?? 'N/A', 0, 20) . '...');
                    $this->command->info("   Membership expires: " . ($responseData['membership']['expire_date'] ?? 'N/A'));
                } else {
                    $this->command->error("❌ Failed: " . $userData['email']);
                    $this->command->error("   Status: " . $response->status());
                    $this->command->error("   Response: " . $response->body());
                }
            } catch (\Exception $e) {
                $this->command->error("❌ Exception for " . $userData['email'] . ": " . $e->getMessage());
            }
            
            // Add a small delay between requests
            sleep(1);
        }
        
        $this->command->info('Test user registration completed!');
    }
}
