<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Models\User\Language;
use App\Models\User\Menu;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use App\Models\User\UserPermission;
use App\Models\User\UserPaymentGeteway;
use App\Models\User\UserEmailTemplate;
use App\Models\User\HomeSection;
use App\Models\User\UserShopSetting;
use App\Models\User\SEO;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DirectTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->command->info('Creating test users directly in database...');
        
        // Get the trial package (ID 26)
        $package = Package::findOrFail(26);
        $this->command->info("Using package: {$package->title} (ID: {$package->id})");
        $this->command->info("Package price: {$package->price}, Trial days: {$package->trial_days}");
        
        // Create 1 test user
        for ($i = 1; $i <= 1; $i++) {
            $email = 'testuser' . $i . '_' . Str::random(8) . '@example.com';
            $username = 'testuser' . $i . '_' . Str::random(6);
            
            $this->command->info("Creating user {$i}: {$email}");
            
            try {
                // Create user
                $user = User::create([
                    'first_name' => 'Test',
                    'last_name' => 'User' . $i,
                    'company_name' => 'Test Company ' . $i,
                    'email' => $email,
                    'phone' => '123456789' . $i,
                    'username' => $username,
                    'password' => bcrypt('123123123'),
                    'status' => 1,
                    'message' => 'شكراً على التسجيل في منصة تعاريف انت الآن على الباقة المميزة لمدة ' . $package->trial_days . ' أيام',
                    'address' => 'Test Address ' . $i,
                    'city' => 'Test City',
                    'state' => 'Test State',
                    'country' => 'Test Country',
                    'verification_link' => Str::random(32),
                    'referral_code' => strtoupper(Str::random(8)),
                    'referred_by' => null,
                    'account_type' => 'tenant',
                    'tenant_id' => null,
                ]);
                
                // Create membership
                $startDate = now();
                $expireDate = now()->addDays($package->trial_days);
                
                $membership = Membership::create([
                    'package_price' => $package->price,
                    'discount' => 0,
                    'coupon_code' => null,
                    'price' => $package->price,
                    'currency' => 'USD',
                    'currency_symbol' => '$',
                    'payment_method' => 'test',
                    'transaction_id' => 'TEST_' . Str::random(8),
                    'status' => 1,
                    'is_trial' => 1,
                    'trial_days' => $package->trial_days,
                    'receipt' => null,
                    'transaction_details' => 'Test registration',
                    'settings' => json_encode(['test' => true]),
                    'package_id' => $package->id,
                    'user_id' => $user->id,
                    'start_date' => $startDate,
                    'expire_date' => $expireDate,
                    'conversation_id' => null
                ]);
                
                // Create basic settings
                BasicSetting::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'website_title' => 'Test Website ' . $i,
                    'company_name' => 'Test Company ' . $i,
                    'base_currency_symbol' => '$',
                    'base_currency_text' => 'USD',
                    'base_currency_symbol_position' => 'left',
                    'base_currency_text_position' => 'right',
                ]);
                
                // Create user permissions
                $features = json_decode($package->features, true) ?? [];
                $features[] = "Contact";
                UserPermission::create([
                    'package_id' => $package->id,
                    'user_id' => $user->id,
                    'permissions' => json_encode($features)
                ]);
                
                // Create payment gateways
                $payment_keywords = ['flutterwave', 'razorpay', 'paytm', 'paystack', 'instamojo', 'stripe', 'paypal', 'mollie', 'mercadopago', 'authorize.net', 'phonepe'];
                foreach ($payment_keywords as $keyword) {
                    UserPaymentGeteway::create([
                        'title' => null,
                        'user_id' => $user->id,
                        'details' => null,
                        'keyword' => $keyword,
                        'subtitle' => null,
                        'name' => ucfirst($keyword),
                        'type' => 'automatic',
                        'information' => null
                    ]);
                }
                
                // Create email templates
                $templates = ['email_verification', 'product_order', 'reset_password', 'room_booking', 'payment_received', 'payment_cancelled', 'course_enrolment', 'course_enrolment_approved', 'course_enrolment_rejected', 'donation', 'donation_approved'];
                foreach ($templates as $template) {
                    UserEmailTemplate::create([
                        'user_id' => $user->id,
                        'email_type' => $template,
                        'email_subject' => null,
                        'email_body' => '<p></p>',
                    ]);
                }
                
                // Create home section
                HomeSection::create([
                    'user_id' => $user->id,
                ]);
                
                // Create shop settings
                UserShopSetting::create([
                    'user_id' => $user->id,
                    'is_shop' => 1,
                    'catalog_mode' => 0,
                    'item_rating_system' => 1,
                    'tax' => 0,
                ]);
                
                // Create SEO
                SEO::create([
                    'user_id' => $user->id,
                    'home_meta_description' => 'Test website description for user ' . $i,
                ]);
                
                $this->command->info("✅ User created successfully:");
                $this->command->info("   Email: {$user->email}");
                $this->command->info("   Username: {$user->username}");
                $this->command->info("   Password: 123123123");
                $this->command->info("   Membership expires: {$expireDate->toDateString()}");
                $this->command->info("   Package: {$package->title} (Price: {$package->price})");
                
            } catch (\Exception $e) {
                $this->command->error("❌ Failed to create user {$i}: " . $e->getMessage());
            }
        }
        
        $this->command->info('Test users creation completed!');
        $this->command->info('You can now test login with any of these users using password: 123123123');
    }
}
