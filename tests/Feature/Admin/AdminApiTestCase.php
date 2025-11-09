<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class AdminApiTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * Ensure shared admin tables exist before running tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePasswordResetsTable();
        $this->ensureRolesTable();
        $this->ensureUsersTable();
        $this->ensureDailyTables();
        $this->ensureDomainTables();
        $this->ensureMarketingTables();
        $this->ensureSupportTables();
        $this->ensureCrmTables();
        $this->ensureAnalyticsTables();
        $this->ensurePackagesTable();
        $this->ensureSanctumTables();
        $this->ensureAdminImpersonationsTable();
        $this->resetAdminData();
    }

    /**
     * Authenticate a freshly created admin for the admin API guard.
     */
    protected function signInAdmin(array $attributes = []): Admin
    {
        $admin = Admin::factory()->create($attributes);

        Sanctum::actingAs($admin, ['*'], config('admin-api.guard'));

        return $admin;
    }

    /**
     * Ensure the password_resets table exists for auth flows.
     */
    private function ensurePasswordResetsTable(): void
    {
        if (Schema::hasTable('password_resets')) {
            return;
        }

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Ensure the roles table exists so admin relations can load.
     */
    private function ensureRolesTable(): void
    {
        if (Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('permissions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Ensure the users table exists for related factories.
     */
    private function ensureUsersTable(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->string('username')->unique();
                $table->string('phone')->nullable();
                $table->boolean('email_verified')->default(false);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->boolean('status')->default(true);
                $table->string('account_type')->default('tenant');
                $table->boolean('active')->default(true);
                $table->string('referral_code')->nullable();
                $table->string('referral_id')->nullable();
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('uuid')->unique()->after('id');
            });
        }

        if (!Schema::hasColumn('users', 'referral_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_id')->nullable()->after('referral_code');
            });
        }
    }

    /**
     * Ensure supporting tables for daily module exist.
     */
    private function ensureDailyTables(): void
    {
        Schema::dropIfExists('users_api_customers_reminders');
        Schema::create('users_api_customers_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');
            $table->unsignedTinyInteger('priority')->default(1);
            $table->timestamp('datetime')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('users_api_customers_appointments');
        Schema::create('users_api_customers_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');
            $table->string('type')->nullable();
            $table->unsignedTinyInteger('priority')->default(1);
            $table->text('note')->nullable();
            $table->timestamp('datetime')->nullable();
            $table->unsignedInteger('duration')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('rm_reminders');
        Schema::create('rm_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->unsignedBigInteger('rental_id')->nullable();
            $table->date('due_on')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->date('snooze_until')->nullable();
            $table->timestamps();
        });

        if (!Schema::hasTable('api_customers')) {
            Schema::create('api_customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        Schema::dropIfExists('rm_rentals');
        Schema::create('rm_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('memberships');
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('package_id')->nullable();
            $table->decimal('package_price', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('currency_symbol', 5)->default('$');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->boolean('is_trial')->default(false);
            $table->unsignedInteger('trial_days')->default(0);
            $table->string('receipt')->nullable();
            $table->json('transaction_details')->nullable();
            $table->json('settings')->nullable();
            $table->date('start_date')->nullable();
            $table->date('expire_date')->nullable();
            $table->boolean('modified')->default(false);
            $table->string('conversation_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Ensure supporting tables for domains module exist.
     */
    private function ensureDomainTables(): void
    {
        Schema::dropIfExists('user_custom_domains');

        Schema::create('user_custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_domain')->nullable();
            $table->string('current_domain')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Ensure supporting tables for marketing module exist.
     */
    private function ensureMarketingTables(): void
    {
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('basic_settings');
        Schema::dropIfExists('languages');

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_default')->default(false);
            $table->boolean('rtl')->default(false);
            $table->timestamps();
        });

        Schema::create('basic_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->string('whatsapp_service')->nullable();
            $table->boolean('whatsapp_notifications_enabled')->default(false);
            $table->string('meta_access_token')->nullable();
            $table->string('meta_phone_number_id')->nullable();
            $table->string('meta_business_account_id')->nullable();
            $table->string('evolution_api_url')->nullable();
            $table->string('evolution_api_key')->nullable();
            $table->string('evolution_instance_name')->nullable();
            $table->string('evolution_phone_number')->nullable();
            $table->boolean('welcome_message_enabled')->default(false);
            $table->text('welcome_message_text')->nullable();
            $table->unsignedInteger('welcome_message_delay')->default(5);
            $table->string('welcome_message_template')->nullable();
            $table->string('welcome_message_api')->nullable();
            $table->boolean('subscription_expiration_enabled')->default(false);
            $table->text('subscription_expiration_text')->nullable();
            $table->unsignedInteger('subscription_expiration_days_before')->default(3);
            $table->string('subscription_expiration_template')->nullable();
            $table->string('subscription_expiration_send_time')->nullable();
            $table->string('subscription_expiration_api')->nullable();
            $table->boolean('subscription_expired_enabled')->default(false);
            $table->text('subscription_expired_text')->nullable();
            $table->string('subscription_expired_template')->nullable();
            $table->string('subscription_expired_send_time')->nullable();
            $table->string('subscription_expired_api')->nullable();
            $table->boolean('password_reset_enabled')->default(false);
            $table->text('password_reset_text')->nullable();
            $table->string('password_reset_template')->nullable();
            $table->string('password_reset_api')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->text('content');
            $table->string('type');
            $table->string('language', 5);
            $table->string('variables')->nullable();
            $table->boolean('status')->default(true);
            $table->unsignedInteger('character_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Ensure supporting tables for support/inquiries module exist.
     */
    private function ensureSupportTables(): void
    {
        if (!Schema::hasTable('api_customers')) {
            Schema::create('api_customers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone_number')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_customer_inquiry')) {
            Schema::create('api_customer_inquiry', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('api_customers')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('phone_number')->nullable();
                $table->text('message')->nullable();
                $table->string('inquiry_type')->nullable();
                $table->string('property_type')->nullable();
                $table->decimal('budget', 12, 2)->nullable();
                $table->string('currency', 10)->nullable();
                $table->integer('bedrooms')->nullable();
                $table->integer('bathrooms')->nullable();
                $table->decimal('min_area_sqm', 12, 2)->nullable();
                $table->decimal('max_area_sqm', 12, 2)->nullable();
                $table->boolean('furnished')->default(false);
                $table->string('urgency')->nullable();
                $table->string('location')->nullable();
                $table->string('country_code', 10)->nullable();
                $table->string('region_code', 10)->nullable();
                $table->string('region_name')->nullable();
                $table->string('city')->nullable();
                $table->string('district')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->decimal('location_confidence', 5, 2)->nullable();
                $table->string('source_channel')->nullable();
                $table->string('lang', 10)->nullable();
                $table->json('detected_entities_json')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Ensure supporting tables for CRM module exist.
     */
    private function ensureCrmTables(): void
    {
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('admin_crm_cards');

        Schema::create('admin_crm_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('order')->default(0);
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->default('manual');
            $table->string('status')->default('new');
            $table->foreignId('stage_id')->nullable()->constrained('admin_crm_cards')->nullOnDelete();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('converted_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        DB::table('admin_crm_cards')->insert([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'New',
            'slug' => 'new',
            'order' => 1,
            'color' => '#2563eb',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Ensure supporting tables for analytics module exist.
     */
    private function ensureAnalyticsTables(): void
    {
        if (!Schema::hasTable('user_properties')) {
            Schema::create('user_properties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedTinyInteger('status')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_affiliate_users')) {
            Schema::create('api_affiliate_users', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('affiliate_transactions')) {
            Schema::create('affiliate_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('affiliate_user_id')->nullable()->constrained('api_affiliate_users')->nullOnDelete();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_activity_logs')) {
            Schema::create('user_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
                $table->string('action');
                $table->text('description')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Ensure the packages (plans) table exists.
     */
    private function ensurePackagesTable(): void
    {
        if (Schema::hasTable('packages')) {
            return;
        }

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug')->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('term')->default('monthly');
            $table->string('icon')->nullable();
            $table->unsignedTinyInteger('featured')->default(0);
            $table->boolean('is_trial')->default(false);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->boolean('is_active')->default(true);
            $table->json('features')->nullable();
            $table->json('new_features')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();
            $table->unsignedInteger('number_of_vcards')->default(0);
            $table->unsignedInteger('project_limit_number')->default(0);
            $table->unsignedInteger('real_estate_limit_number')->default(0);
            $table->unsignedInteger('video_size_limit')->default(0);
            $table->unsignedInteger('file_size_limit')->default(0);
            $table->unsignedInteger('serial_number')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Ensure Sanctum personal access tokens table exists.
     */
    private function ensureSanctumTables(): void
    {
        if (!Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Ensure admin impersonations table exists.
     */
    private function ensureAdminImpersonationsTable(): void
    {
        if (!Schema::hasTable('admin_impersonations')) {
            Schema::create('admin_impersonations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('token_id')->nullable();
                $table->timestamp('started_at')->useCurrent();
                $table->timestamp('ended_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('reason')->nullable();
                $table->integer('actions_count')->default(0);
                $table->enum('status', ['active', 'ended', 'expired', 'revoked'])->default('active');
                $table->timestamps();

                $table->index('admin_id');
                $table->index('user_id');
                $table->index('status');
                $table->index('started_at');
            });
        }
    }

    /**
     * Reset admin-related tables between tests to avoid unique constraint collisions.
     */
    private function resetAdminData(): void
    {
        if (!Schema::hasTable('admins')) {
            return;
        }

        Schema::disableForeignKeyConstraints();
        DB::table('admins')->truncate();

        if (Schema::hasTable('password_resets')) {
            DB::table('password_resets')->truncate();
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->truncate();
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->truncate();
        }

        if (Schema::hasTable('users_api_customers_reminders')) {
            DB::table('users_api_customers_reminders')->truncate();
        }

        if (Schema::hasTable('users_api_customers_appointments')) {
            DB::table('users_api_customers_appointments')->truncate();
        }

        if (Schema::hasTable('rm_reminders')) {
            DB::table('rm_reminders')->truncate();
        }

        if (Schema::hasTable('rm_rentals')) {
            DB::table('rm_rentals')->truncate();
        }

        if (Schema::hasTable('memberships')) {
            DB::table('memberships')->truncate();
        }

        if (Schema::hasTable('api_customers')) {
            DB::table('api_customers')->truncate();
        }

        if (Schema::hasTable('packages')) {
            DB::table('packages')->truncate();
        }

        if (Schema::hasTable('api_customer_inquiry')) {
            DB::table('api_customer_inquiry')->truncate();
        }

        if (Schema::hasTable('api_customers')) {
            DB::table('api_customers')->truncate();
        }

        if (Schema::hasTable('whatsapp_templates')) {
            DB::table('whatsapp_templates')->truncate();
        }

        if (Schema::hasTable('basic_settings')) {
            DB::table('basic_settings')->truncate();
        }

        if (Schema::hasTable('languages')) {
            DB::table('languages')->truncate();
        }

        if (Schema::hasTable('user_custom_domains')) {
            DB::table('user_custom_domains')->truncate();
        }

        if (Schema::hasTable('lead_activities')) {
            DB::table('lead_activities')->truncate();
        }

        if (Schema::hasTable('leads')) {
            DB::table('leads')->truncate();
        }

        if (Schema::hasTable('admin_crm_cards')) {
            DB::table('admin_crm_cards')->truncate();

            DB::table('admin_crm_cards')->insert([
                'uuid' => (string) Str::uuid(),
                'name' => 'New',
                'slug' => 'new',
                'order' => 1,
                'color' => '#2563eb',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('user_properties')) {
            DB::table('user_properties')->truncate();
        }

        if (Schema::hasTable('api_affiliate_users')) {
            DB::table('api_affiliate_users')->truncate();
        }

        if (Schema::hasTable('affiliate_transactions')) {
            DB::table('affiliate_transactions')->truncate();
        }

        if (Schema::hasTable('user_activity_logs')) {
            DB::table('user_activity_logs')->truncate();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->truncate();
        }

        if (Schema::hasTable('admin_impersonations')) {
            DB::table('admin_impersonations')->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}

