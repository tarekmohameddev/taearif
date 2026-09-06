<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class AdminApiTestCase extends TestCase
{
    /**
     * When false, setUp will not truncate shared admin tables.
     * Use for legacy web admin feature tests against imported dumps.
     */
    protected bool $shouldResetAdminData = true;

    /**
     * Ensure shared admin tables exist before running tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePasswordResetsTable();
        $this->ensureRolesTable();
        $this->ensureAdminsTable();
        $this->ensureUsersTable();
        $this->ensureDailyTables();
        $this->ensureDomainTables();
        $this->ensureMarketingTables();
        $this->ensureSupportTables();
        $this->ensureReferralTables();
        $this->ensurePlatformTables();
        $this->ensureCrmTables();
        $this->ensureAnalyticsTables();
        $this->ensureDashboardDailyVisitsTable();
        $this->ensurePackagesTable();
        $this->ensureUserBasicSettingsTable();
        $this->ensureMembershipsJsonColumns();
        $this->ensureRegisterUserListingTables();
        $this->ensureSanctumTables();
        $this->ensureAdminImpersonationsTable();

        if ($this->shouldResetAdminData) {
            $this->resetAdminData();
        }
    }

    /**
     * Create a table only if it does not exist; ignore "already exists" when using imported DB.
     */
    private function createTableIfNotExists(string $table, \Closure $callback): void
    {
        if (Schema::hasTable($table)) {
            return;
        }
        try {
            Schema::create($table, $callback);
        } catch (\Illuminate\Database\QueryException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
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
     * Ensure the admins table exists before dependent tables add foreign keys.
     */
    private function ensureAdminsTable(): void
    {
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('role_id')->nullable()->index();
                $table->string('username')->unique();
                $table->string('email')->unique();
                $table->string('password');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('image')->nullable();
                $table->boolean('status')->default(true);
                $table->string('remember_token', 100)->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->json('permissions')->nullable();
                $table->timestamps();
            });

            return;
        }

        $columns = [
            'uuid' => fn (Blueprint $table) => $table->uuid('uuid')->nullable()->unique()->after('id'),
            'role_id' => fn (Blueprint $table) => $table->unsignedBigInteger('role_id')->nullable()->index()->after('uuid'),
            'username' => fn (Blueprint $table) => $table->string('username')->unique()->after('role_id'),
            'email' => fn (Blueprint $table) => $table->string('email')->unique()->after('username'),
            'password' => fn (Blueprint $table) => $table->string('password')->after('email'),
            'first_name' => fn (Blueprint $table) => $table->string('first_name')->nullable()->after('password'),
            'last_name' => fn (Blueprint $table) => $table->string('last_name')->nullable()->after('first_name'),
            'image' => fn (Blueprint $table) => $table->string('image')->nullable()->after('last_name'),
            'status' => fn (Blueprint $table) => $table->boolean('status')->default(true)->after('image'),
            'remember_token' => fn (Blueprint $table) => $table->string('remember_token', 100)->nullable()->after('status'),
            'email_verified_at' => fn (Blueprint $table) => $table->timestamp('email_verified_at')->nullable()->after('remember_token'),
            'last_login_at' => fn (Blueprint $table) => $table->timestamp('last_login_at')->nullable()->after('email_verified_at'),
            'permissions' => fn (Blueprint $table) => $table->json('permissions')->nullable()->after('last_login_at'),
        ];

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn('admins', $column)) {
                Schema::table('admins', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }
    }

    /**
     * Ensure the users table exists for related factories.
     */
    private function ensureUsersTable(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->nullable()->unique();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('referred_by')->nullable();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->unique();
                $table->string('username')->unique();
                $table->string('photo')->nullable();
                $table->string('company_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('address')->nullable();
                $table->string('country')->nullable();
                $table->boolean('email_verified')->default(false);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->unsignedTinyInteger('status')->default(1);
                $table->string('account_type')->default('tenant');
                $table->boolean('active')->default(true);
                $table->boolean('featured')->default(false);
                $table->boolean('online_status')->default(false);
                $table->boolean('subscribed')->default(false);
                $table->decimal('subscription_amount', 10, 2)->default(0);
                $table->timestamp('trial_ends_at')->nullable();
                $table->unsignedInteger('rbac_version')->default(0);
                $table->timestamp('rbac_seeded_at')->nullable();
                $table->string('referral_code')->nullable();
                $table->string('referral_id')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        $columns = [
            'tenant_id' => fn (Blueprint $table) => $table->unsignedBigInteger('tenant_id')->nullable()->after('id'),
            'referred_by' => fn (Blueprint $table) => $table->unsignedBigInteger('referred_by')->nullable()->after('tenant_id'),
            'photo' => fn (Blueprint $table) => $table->string('photo')->nullable()->after('last_name'),
            'company_name' => fn (Blueprint $table) => $table->string('company_name')->nullable()->after('username'),
            'city' => fn (Blueprint $table) => $table->string('city')->nullable()->after('company_name'),
            'state' => fn (Blueprint $table) => $table->string('state')->nullable()->after('city'),
            'address' => fn (Blueprint $table) => $table->string('address')->nullable()->after('state'),
            'country' => fn (Blueprint $table) => $table->string('country')->nullable()->after('address'),
            'featured' => fn (Blueprint $table) => $table->boolean('featured')->default(false)->after('active'),
            'online_status' => fn (Blueprint $table) => $table->boolean('online_status')->default(false)->after('featured'),
            'subscribed' => fn (Blueprint $table) => $table->boolean('subscribed')->default(false)->after('online_status'),
            'subscription_amount' => fn (Blueprint $table) => $table->decimal('subscription_amount', 10, 2)->default(0)->after('subscribed'),
            'trial_ends_at' => fn (Blueprint $table) => $table->timestamp('trial_ends_at')->nullable()->after('subscription_amount'),
            'rbac_version' => fn (Blueprint $table) => $table->unsignedInteger('rbac_version')->default(0)->after('trial_ends_at'),
            'rbac_seeded_at' => fn (Blueprint $table) => $table->timestamp('rbac_seeded_at')->nullable()->after('rbac_version'),
            'referral_code' => fn (Blueprint $table) => $table->string('referral_code')->nullable()->after('active'),
            'uuid' => fn (Blueprint $table) => $table->uuid('uuid')->nullable()->unique()->after('id'),
            'deleted_at' => fn (Blueprint $table) => $table->softDeletes(),
        ];

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }

        if (!Schema::hasColumn('users', 'referral_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_id')->nullable()->after('referral_code');
            });
        }

        if (!Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('active');
            });
        }
    }

    /**
     * Ensure supporting tables for daily module exist.
     */
    private function ensureDailyTables(): void
    {
        $this->createTableIfNotExists('users_api_customers_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('title');
            $table->unsignedTinyInteger('priority')->default(1);
            $table->timestamp('datetime')->nullable();
            $table->timestamps();
        });

        $this->createTableIfNotExists('users_api_customers_appointments', function (Blueprint $table) {
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

        $this->createTableIfNotExists('rm_reminders', function (Blueprint $table) {
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

        $this->createTableIfNotExists('api_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ([
            'phone_number' => fn (Blueprint $table) => $table->string('phone_number')->nullable()->after('email'),
            'password' => fn (Blueprint $table) => $table->string('password')->nullable()->after('phone_number'),
            'deleted_at' => fn (Blueprint $table) => $table->softDeletes(),
        ] as $column => $callback) {
            if (!Schema::hasColumn('api_customers', $column)) {
                Schema::table('api_customers', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }

        $this->createTableIfNotExists('rm_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('currency', 3)->default('SAR');
            $table->decimal('total_rental_amount', 12, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
        });

        foreach ([
            'currency' => fn (Blueprint $table) => $table->char('currency', 3)->default('SAR')->after('user_id'),
            'total_rental_amount' => fn (Blueprint $table) => $table->decimal('total_rental_amount', 12, 2)->nullable()->after('currency'),
            'status' => fn (Blueprint $table) => $table->string('status')->default('draft')->after('total_rental_amount'),
            'tenant_full_name' => fn (Blueprint $table) => $table->string('tenant_full_name', 150)->nullable()->after('user_id'),
            'tenant_phone' => fn (Blueprint $table) => $table->string('tenant_phone', 32)->nullable()->after('tenant_full_name'),
            'deleted_at' => fn (Blueprint $table) => $table->softDeletes(),
        ] as $column => $callback) {
            if (!Schema::hasColumn('rm_rentals', $column)) {
                Schema::table('rm_rentals', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }

        $this->createTableIfNotExists('memberships', function (Blueprint $table) {
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

        if (Schema::hasTable('memberships') && !Schema::hasColumn('memberships', 'uuid')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->unique()->after('id');
            });
        }
    }

    /**
     * Ensure supporting tables for domains module exist.
     */
    private function ensureDomainTables(): void
    {
        if (Schema::hasTable('user_custom_domains')) {
            return;
        }

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
        if (Schema::hasTable('languages') && Schema::hasTable('basic_settings') && Schema::hasTable('whatsapp_templates')) {
            return;
        }

        if (!Schema::hasTable('languages')) {
            Schema::create('languages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_default')->default(false);
                $table->boolean('rtl')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('basic_settings')) {
            Schema::create('basic_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->nullable();
            $table->string('website_title')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('email_verification_status')->default(false);
            $table->string('base_color')->nullable();
            $table->string('whatsapp_service')->nullable();
            $table->boolean('whatsapp_notifications_enabled')->default(false);
            $table->string('meta_access_token')->nullable();
            $table->string('meta_phone_number_id')->nullable();
            $table->string('meta_business_account_id')->nullable();
            $table->string('meta_template_name')->nullable();
            $table->string('meta_template_language')->nullable();
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
            $table->boolean('whatsapp_status')->default(false);
            $table->string('whatsapp_number')->nullable();
            $table->boolean('maintenance_status')->default(false);
            $table->text('maintainance_text')->nullable();
            $table->string('maintenance_img')->nullable();
            $table->string('secret_path')->nullable();
            $table->string('logo')->nullable();
            $table->string('footer_logo')->nullable();
            $table->string('favicon')->nullable();
            $table->string('preloader')->nullable();
            $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_templates')) {
            try {
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
            } catch (\Illuminate\Database\QueryException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
        }
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
     * Ensure supporting tables for platform settings module exist.
     */
    private function ensurePlatformTables(): void
    {
        if (! Schema::hasTable('basic_extendeds')) {
        Schema::create('basic_extendeds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_id')->nullable();
            $table->boolean('is_smtp')->default(false);
            $table->boolean('email_notifications_enabled')->default(false);
            $table->string('smtp_host')->nullable();
            $table->string('smtp_port')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('from_mail')->nullable();
            $table->string('from_name')->nullable();
            $table->string('to_mail')->nullable();
            $table->string('base_currency_symbol')->nullable();
            $table->string('base_currency_symbol_position')->nullable();
            $table->string('base_currency_text')->nullable();
            $table->string('base_currency_text_position')->nullable();
            $table->decimal('base_currency_rate', 12, 6)->default(1.000000);
            $table->string('timezone')->nullable();
        });
        }

        if (! Schema::hasTable('seos')) {
        Schema::create('seos', function (Blueprint $table) {
            $table->id();
            $table->text('meta_keywords')->nullable();
            $table->text('meta_description')->nullable();
            $table->longText('google_analytics')->nullable();
            $table->longText('facebook_pixel')->nullable();
            $table->timestamps();
        });
        }
    }

    /**
     * Ensure supporting tables for referrals module exist.
     */
    private function ensureReferralTables(): void
    {
        $this->createTableIfNotExists('api_affiliate_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('fullname')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban')->nullable();
            $table->decimal('commission_percentage', 5, 2)->default(0);
            $table->decimal('pending_amount', 10, 2)->default(0);
            $table->string('request_status')->default('pending');
            $table->date('start_date_value')->nullable();
            $table->date('to_date_value')->nullable();
            $table->timestamps();
        });

        $this->createTableIfNotExists('affiliate_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->nullable()->constrained('api_affiliate_users')->nullOnDelete();
            $table->foreignId('referral_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('pending');
            $table->string('image')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Ensure supporting tables for CRM module exist.
     */
    private function ensureCrmTables(): void
    {
        $this->createTableIfNotExists('admin_crm_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('order')->default(0);
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $this->createTableIfNotExists('leads', function (Blueprint $table) {
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

        $this->createTableIfNotExists('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('admin_crm_cards') && DB::table('admin_crm_cards')->count() === 0) {
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
    }

    /**
     * Ensure supporting tables for analytics module exist.
     */
    private function ensureAnalyticsTables(): void
    {
        if (!Schema::hasTable('user_projects')) {
            Schema::create('user_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('published')->default(false);
                $table->boolean('featured')->default(false);
                $table->unsignedTinyInteger('complete_status')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('user_properties')) {
            Schema::create('user_properties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedBigInteger('project_id')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->string('purpose')->nullable();
                $table->string('listing_purpose')->nullable();
                $table->string('unit_status')->nullable();
                $table->string('publish_status')->nullable();
                $table->string('property_type')->nullable();
                $table->string('completion_status')->nullable();
                $table->unsignedInteger('area')->default(0);
                $table->unsignedTinyInteger('status')->default(1);
                $table->boolean('is_active')->default(true);
                $table->boolean('featured')->default(false);
                $table->timestamps();
            });
        }

        foreach ([
            'project_id' => fn (Blueprint $table) => $table->unsignedBigInteger('project_id')->nullable()->after('user_id'),
            'price' => fn (Blueprint $table) => $table->decimal('price', 12, 2)->nullable()->after('project_id'),
            'purpose' => fn (Blueprint $table) => $table->string('purpose')->nullable()->after('price'),
            'listing_purpose' => fn (Blueprint $table) => $table->string('listing_purpose')->nullable()->after('purpose'),
            'unit_status' => fn (Blueprint $table) => $table->string('unit_status')->nullable()->after('listing_purpose'),
            'publish_status' => fn (Blueprint $table) => $table->string('publish_status')->nullable()->after('unit_status'),
            'property_type' => fn (Blueprint $table) => $table->string('property_type')->nullable()->after('publish_status'),
            'completion_status' => fn (Blueprint $table) => $table->string('completion_status')->nullable()->after('property_type'),
            'area' => fn (Blueprint $table) => $table->unsignedInteger('area')->default(0)->after('property_type'),
            'featured' => fn (Blueprint $table) => $table->boolean('featured')->default(false)->after('is_active'),
        ] as $column => $callback) {
            if (!Schema::hasColumn('user_properties', $column)) {
                Schema::table('user_properties', function (Blueprint $table) use ($callback) {
                    $callback($table);
                });
            }
        }

        if (!Schema::hasTable('sales')) {
            Schema::create('sales', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->decimal('sale_price', 12, 2)->nullable();
                $table->dateTime('sale_date')->nullable();
                $table->string('status')->default('pending');
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

    private function ensureDashboardDailyVisitsTable(): void
    {
        if (Schema::hasTable('dashboard_daily_visits')) {
            return;
        }

        Schema::create('dashboard_daily_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_owner_id')->constrained('users')->cascadeOnDelete();
            $table->date('visited_on');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('visits_count')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'visited_on']);
            $table->index('visited_on');
            $table->index(['tenant_owner_id', 'visited_on']);
        });
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
     * Ensure user_basic_settings exists so register-user listing can eager-load it.
     */
    private function ensureUserBasicSettingsTable(): void
    {
        $this->createTableIfNotExists('user_basic_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('company_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Testing memberships may have JSON CHECKs that reject the string "Trial".
     */
    private function ensureMembershipsJsonColumns(): void
    {
        if (! Schema::hasTable('memberships')) {
            return;
        }

        try {
            DB::statement('ALTER TABLE memberships MODIFY transaction_details LONGTEXT NULL');
        } catch (\Throwable $e) {
            // Column may already be unconstrained.
        }

        try {
            DB::statement('ALTER TABLE memberships MODIFY settings LONGTEXT NULL');
        } catch (\Throwable $e) {
            // Column may already be unconstrained.
        }
    }

    /**
     * Stub tables required to render admin.register_user.index.
     */
    private function ensureRegisterUserListingTables(): void
    {
        $this->createTableIfNotExists('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
        });

        $this->createTableIfNotExists('offline_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
        });

        $this->createTableIfNotExists('user_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->boolean('logo_uploaded')->default(false);
            $table->boolean('favicon_uploaded')->default(false);
            $table->boolean('website_named')->default(false);
            $table->boolean('homepage_updated')->default(false);
            $table->timestamps();
        });

        $this->createTableIfNotExists('api_general_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('site_name')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamps();
        });

        if (!Schema::hasColumn('api_general_settings', 'site_name')) {
            Schema::table('api_general_settings', function (Blueprint $table) {
                $table->string('site_name')->nullable()->after('user_id');
            });
        }

        $this->createTableIfNotExists('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
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
        DB::table('admins')->delete();

        if (Schema::hasTable('password_resets')) {
            DB::table('password_resets')->delete();
        }

        if (Schema::hasTable('roles')) {
            DB::table('roles')->delete();
        }

        if (Schema::hasTable('users')) {
            DB::table('users')->delete();
        }

        if (Schema::hasTable('users_api_customers_reminders')) {
            DB::table('users_api_customers_reminders')->delete();
        }

        if (Schema::hasTable('users_api_customers_appointments')) {
            DB::table('users_api_customers_appointments')->delete();
        }

        if (Schema::hasTable('rm_reminders')) {
            DB::table('rm_reminders')->delete();
        }

        if (Schema::hasTable('rm_rentals')) {
            DB::table('rm_rentals')->delete();
        }

        if (Schema::hasTable('memberships')) {
            DB::table('memberships')->delete();
        }

        if (Schema::hasTable('api_customers')) {
            DB::table('api_customers')->delete();
        }

        if (Schema::hasTable('packages')) {
            DB::table('packages')->delete();
        }

        if (Schema::hasTable('api_customer_inquiry')) {
            DB::table('api_customer_inquiry')->delete();
        }

        if (Schema::hasTable('api_customers')) {
            DB::table('api_customers')->delete();
        }

        if (Schema::hasTable('whatsapp_templates')) {
            DB::table('whatsapp_templates')->delete();
        }

        if (Schema::hasTable('basic_settings')) {
            DB::table('basic_settings')->delete();
        }

        if (Schema::hasTable('basic_extendeds')) {
            DB::table('basic_extendeds')->delete();
        }

        if (Schema::hasTable('seos')) {
            DB::table('seos')->delete();
        }

        if (Schema::hasTable('languages')) {
            DB::table('languages')->delete();
        }

        if (Schema::hasTable('user_custom_domains')) {
            DB::table('user_custom_domains')->delete();
        }

        if (Schema::hasTable('api_domains_settings')) {
            DB::table('api_domains_settings')->delete();
        }

        if (Schema::hasTable('lead_activities')) {
            DB::table('lead_activities')->delete();
        }

        if (Schema::hasTable('leads')) {
            DB::table('leads')->delete();
        }

        if (Schema::hasTable('admin_crm_cards')) {
            DB::table('admin_crm_cards')->delete();

            if (DB::table('admin_crm_cards')->where('slug', 'new')->doesntExist()) {
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
        }

        if (Schema::hasTable('user_properties')) {
            DB::table('user_properties')->delete();
        }

        if (Schema::hasTable('user_projects')) {
            DB::table('user_projects')->delete();
        }

        if (Schema::hasTable('api_affiliate_users')) {
            DB::table('api_affiliate_users')->delete();
        }

        if (Schema::hasTable('affiliate_transactions')) {
            DB::table('affiliate_transactions')->delete();
        }

        if (Schema::hasTable('user_activity_logs')) {
            DB::table('user_activity_logs')->delete();
        }

        if (Schema::hasTable('sales')) {
            DB::table('sales')->delete();
        }

        if (Schema::hasTable('dashboard_daily_visits')) {
            DB::table('dashboard_daily_visits')->delete();
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->delete();
        }

        if (Schema::hasTable('admin_impersonations')) {
            DB::table('admin_impersonations')->delete();
        }

        Schema::enableForeignKeyConstraints();
    }
}

