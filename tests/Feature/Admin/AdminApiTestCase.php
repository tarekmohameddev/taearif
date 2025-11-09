<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                $table->timestamps();
            });
        } elseif (!Schema::hasColumn('users', 'uuid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('uuid')->unique()->after('id');
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

        Schema::dropIfExists('api_customers');
        Schema::create('api_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->timestamps();
        });

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

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->truncate();
        }

        if (Schema::hasTable('admin_impersonations')) {
            DB::table('admin_impersonations')->truncate();
        }

        Schema::enableForeignKeyConstraints();
    }
}

